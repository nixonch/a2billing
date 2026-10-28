#!/usr/bin/env python3
"""Normalize the canonical installation SQL using an isolated test MySQL.

The supplied socket must belong to a test server. No existing database is read
or modified by default. Only the uniquely named database created here is
dropped. --from-database exports an existing test database without modifying it.
"""

import argparse
import os
import re
import subprocess
import sys
import uuid
import xml.etree.ElementTree as ET


ARCHIVE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.join(os.path.dirname(ARCHIVE), 'mysql-8.x')
SNAPSHOT = os.path.join(ROOT, 'a2billing-install.sql')


def read(path):
    with open(path, encoding='utf-8') as source:
        return source.read()


def identifier(name):
    return '`' + name.replace('`', '``') + '`'


def run(command, data=None):
    process = subprocess.Popen(command, stdin=subprocess.PIPE,
                               stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    output, errors = process.communicate(
        data.encode('utf-8') if data is not None else None)
    if process.returncode:
        raise RuntimeError(errors.decode('utf-8', errors='replace'))
    return output.decode('utf-8')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--socket', required=True, help='isolated test MySQL socket')
    parser.add_argument('--mysql', default='mysql')
    parser.add_argument('--mysqldump', default='mysqldump')
    parser.add_argument('--user', default='root')
    parser.add_argument('--from-database', help='export an existing a2b_* test database')
    parser.add_argument('--check', action='store_true',
                        help='compare with the committed snapshot without writing')
    args = parser.parse_args()
    connection = ['--no-defaults', '--socket=' + args.socket,
                  '--user=' + args.user, '--default-character-set=utf8']
    mysql = [args.mysql] + connection + ['--skip-force']
    database = args.from_database or 'a2b_install_build_' + uuid.uuid4().hex
    if not re.match(r'^a2b_[a-zA-Z0-9_]+$', database):
        parser.error('The source must be an a2b_* test database')
    owns_database = args.from_database is None

    def query(sql, xml=False):
        options = ['--xml'] if xml else ['--batch', '--raw', '--skip-column-names']
        return run(mysql + options + ['--execute=' + sql])

    def execute(sql):
        return run(mysql + [database], sql)

    if owns_database:
        query('CREATE DATABASE ' + identifier(database) +
              ' CHARACTER SET utf8 COLLATE utf8_bin')
    try:
        if owns_database:
            execute(read(SNAPSHOT))
        if query('SELECT version FROM ' + identifier(database) + '.cc_version').strip() != '3.0 (nixonch)':
            raise RuntimeError('Expected A2Billing database version 3.0 (nixonch)')

        tables = query("SELECT TABLE_NAME FROM information_schema.TABLES "
                       "WHERE TABLE_SCHEMA='" + database + "' AND TABLE_TYPE='BASE TABLE' "
                       "ORDER BY TABLE_NAME").splitlines()
        views = query("SELECT TABLE_NAME FROM information_schema.VIEWS "
                      "WHERE TABLE_SCHEMA='" + database + "' ORDER BY TABLE_NAME").splitlines()
        # Every agent table is still referenced by PHP or included .inc files.
        # Keep these until the corresponding application code is removed.
        header = [
            '-- A2Billing 3.0 (nixonch) installation schema for MySQL 8.x.',
            '-- A2Billing / Star2billing S.L.; GNU Affero General Public License v3 or later.',
            '-- Original copyright and license notices are retained in ../mysql-5.x/.',
            '-- Canonical final structure and initial data; for an EMPTY database only.',
            '-- Re-export and validate with ../mysql-5.x/build-install.py.',
            '-- Standard prefix names win exact matches; additional Josko prefixes are included.',
            '-- Agent tables remain because application references still exist.',
            'SET NAMES utf8 COLLATE utf8_bin;',
            'SET @a2b_install_sql_mode = @@SESSION.sql_mode;',
            "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';",
            '',
        ]
        result = '\n'.join(header)

        def definition(name, field):
            xml = ET.fromstring(query('SHOW CREATE TABLE ' + identifier(database) +
                                      '.' + identifier(name), xml=True))
            for element in xml.findall('./row/field'):
                if element.get('name') == field:
                    sql = element.text.replace(identifier(database) + '.', '')
                    sql = re.sub(r'DEFINER=`[^`]*`@`[^`]*`\s*', '', sql)
                    sql = sql.replace('SQL SECURITY DEFINER', 'SQL SECURITY INVOKER')
                    if field == 'Create View':
                        fields = {item.get('name'): item.text for item in xml.findall('./row/field')}
                        sql = 'SET NAMES {0} COLLATE {1};\n'.format(
                            fields['character_set_client'], fields['collation_connection']) + sql
                    return sql
            raise RuntimeError('Missing definition for ' + name)

        for table in tables:
            result += '\n' + definition(table, 'Create Table') + ';\n'

        dump = [args.mysqldump] + connection + [
            '--compact', '--no-create-info', '--skip-triggers', '--skip-add-locks',
            '--skip-disable-keys', '--skip-set-charset', '--skip-tz-utc',
            '--complete-insert', '--hex-blob', '--order-by-primary',
            '--net-buffer-length=16384',
        ]
        # Preserve install-time timestamps instead of freezing the build time.
        dynamic = ['cc_trunk', 'cc_ui_authen']
        ordinary = [table for table in tables if table not in dynamic]
        result += '\n-- Initial data.\n' + run(dump + [database] + ordinary)
        for table in dynamic:
            seed = run(dump + ['--skip-extended-insert', database, table])
            output = []
            for line in seed.splitlines():
                match = re.match(r'INSERT INTO `\w+` \((.*?)\) VALUES \((.*)\);$', line)
                if match:
                    columns = [column.strip(' `') for column in match.group(1).split(',')]
                    values = re.findall(r"'(?:[^'\\]|\\.|'')*'|[^,]+", match.group(2))
                    if len(columns) != len(values):
                        raise RuntimeError('Cannot parse seed values for ' + table)
                    values[columns.index('datecreation' if table == 'cc_ui_authen'
                                         else 'creationdate')] = 'CURRENT_TIMESTAMP'
                    line = line[:match.start(2)] + ','.join(values) + ');'
                output.append(line)
            result += '\n'.join(output) + '\n'

        for view in views:
            result += '\n' + definition(view, 'Create View') + ';\n'
        triggers = query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS "
                         "WHERE TRIGGER_SCHEMA='" + database + "' ORDER BY TRIGGER_NAME").splitlines()
        result += '\nDELIMITER //\n'
        for trigger in triggers:
            charset = query("SELECT CHARACTER_SET_CLIENT,COLLATION_CONNECTION "
                            "FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='" +
                            database + "' AND TRIGGER_NAME='" + trigger + "'").strip().split('\t')
            result += '\nSET NAMES {0} COLLATE {1}//\n'.format(*charset)
            # HEX avoids newline/tab escaping in stored trigger bodies.
            sql = query("SELECT HEX(CONCAT('CREATE TRIGGER `', TRIGGER_NAME, '` ', "
                        "ACTION_TIMING, ' ', EVENT_MANIPULATION, ' ON `', "
                        "EVENT_OBJECT_TABLE, '` FOR EACH ROW ', ACTION_STATEMENT)) "
                        "FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='" +
                        database + "' AND TRIGGER_NAME='" + trigger + "'").strip()
            result += '\n' + bytes.fromhex(sql).decode('utf-8') + '//\n'
        result += ('\nDELIMITER ;\nSET NAMES utf8 COLLATE utf8_bin;\n'
                   'SET SESSION sql_mode = @a2b_install_sql_mode;\n')

        if args.check:
            if read(SNAPSHOT) != result:
                raise RuntimeError('Snapshot differs; rebuild a2billing-install.sql')
            print('Snapshot is reproducible.')
        else:
            with open(SNAPSHOT, 'w', encoding='utf-8') as target:
                target.write(result)
            print('Built {0}: {1} tables, {2} views, {3} triggers.'.format(
                SNAPSHOT, len(tables), len(views), len(triggers)))
    finally:
        if owns_database:
            query('DROP DATABASE ' + identifier(database))


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, OSError) as error:
        sys.exit(str(error))
