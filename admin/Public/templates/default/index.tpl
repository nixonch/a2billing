<!DOCTYPE html>
<html lang="{php} echo LANG; {/php}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{$FAVICONPATH}">
    <title>{$CCMAINTITLE}</title>
    <link rel="stylesheet" href="templates/default/css/index.css" type="text/css">
</head>
<body class="bodygradient">
<div class="container">
    <div class="frame laud">
        <div class="nav">
            <select name="ui_language" id="ui_language" aria-label="Language" style="align-self:flex-end">
                <option value="english" {php} if(LANGUAGE=="english") echo "selected"; {/php}>English</option>
                <option value="ukrainian" {php} if(LANGUAGE=="ukrainian") echo "selected"; {/php}>Українська</option>
                <option value="russian" {php} if(LANGUAGE=="russian") echo "selected"; {/php}>Русский</option>
                <option value="german" {php} if(LANGUAGE=="german") echo "selected"; {/php}>Deutsch</option>
                <option value="brazilian" {php} if(LANGUAGE=="brazilian") echo "selected"; {/php}>Brazilian</option>
                <option value="romanian" {php} if(LANGUAGE=="romanian") echo "selected"; {/php}>Romanian</option>
                <option value="french" {php} if(LANGUAGE=="french") echo "selected"; {/php}>French</option>
                <option value="greek" {php} if(LANGUAGE=="greek") echo "selected"; {/php}>Greek</option>
            </select>
        </div>
        <form class="form-signin" name="form" method="post" action="PP_intro.php" onsubmit="return test()">
            <input type="hidden" name="done" value="submit_log">
            <div style="min-height:5px;display:flex;justify-content:center;align-items:center">
                <div class="login-title" id="warningsignin" role="alert">
                    {if ($error == 1)}
                        {php} echo gettext("AUTHENTICATION REFUSED, please check your user/password!"); {/php}
                    {elseif ($error == 2)}
                        {php} echo gettext("INACTIVE ACCOUNT, Please activate your account!"); {/php}
                    {elseif ($error == 3)}
                        {php} echo gettext("BLOCKED ACCOUNT, Please contact the administrator!"); {/php}
                    {/if}
                </div>
            </div>
            <label for="pr_login">{php} echo gettext("Login"); {/php}</label>
            <input autofocus class="form-styling" type="text" name="pr_login" id="pr_login" autocomplete="username">
            <label for="pr_password">{php} echo gettext("Password"); {/php}</label>
            <input class="form-styling" type="password" name="pr_password" id="pr_password" autocomplete="current-password">
            <input type="checkbox" id="checkbox" class="checkbox">
            <label for="checkbox"><span class="ui"></span>{php} echo gettext("Keep me signed in"); {/php}</label>
            <button class="btn-submit btn-signin" type="submit" name="submit">{php} echo gettext("LOGIN"); {/php}</button>
        </form>
    </div>
    <div class="frameback laud lgrad"></div>
</div>
<div>
    <a id="refresh" role="button" tabindex="0" aria-label="Toggle background" onclick="opback()">
        <svg class="refreshicon" xmlns="http://www.w3.org/2000/svg" width="25px" height="25px" viewBox="0 0 322.447 322.447" aria-hidden="true">
            <path d="M321.832,230.327c-2.133-6.565-9.184-10.154-15.75-8.025l-16.254,5.281C299.785,206.991,305,184.347,305,161.224
                c0-84.089-68.41-152.5-152.5-152.5C68.411,8.724,0,77.135,0,161.224s68.411,152.5,152.5,152.5c6.903,0,12.5-5.597,12.5-12.5
                c0-6.902-5.597-12.5-12.5-12.5c-70.304,0-127.5-57.195-127.5-127.5c0-70.304,57.196-127.5,127.5-127.5
                c70.305,0,127.5,57.196,127.5,127.5c0,19.372-4.371,38.337-12.723,55.568l-5.553-17.096c-2.133-6.564-9.186-10.156-15.75-8.025
                c-6.566,2.134-10.16,9.186-8.027,15.751l14.74,45.368c1.715,5.283,6.615,8.642,11.885,8.642c1.279,0,2.582-0.198,3.865-0.614
                l45.369-14.738C320.371,243.946,323.965,236.895,321.832,230.327z"/>
        </svg>
    </a>
</div>
<script src="javascript/jquery/jquery-1.7.2.min.js"></script>
<script>
    var emptylogin = {php} echo json_encode(gettext('You must enter an user and a password!')); {/php};
</script>
{literal}
<script>
    function test() {
        if (document.form.pr_login.value == "" || document.form.pr_password.value == "") {
            $("#warningsignin").text(emptylogin);
            return false;
        }
        return true;
    }

    $("#ui_language").change(function () {
        self.location.href = "?ui_language=" + $("#ui_language option:selected").val();
    });

    function opback() {
        $(".frameback").toggleClass("lgrad lgrad2");
        $(".frame").toggleClass("opback");
        $("body").toggleClass("bodygradient");
    }

    $("#refresh").keydown(function (event) {
        if (event.keyCode == 13 || event.keyCode == 32) {
            event.preventDefault();
            opback();
        }
    });
</script>
{/literal}
</body>
</html>


