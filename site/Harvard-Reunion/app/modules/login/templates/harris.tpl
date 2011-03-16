{include file="findInclude:modules/login/templates/header.tpl"}

<h1>Harvard College Reunions</h1>

<h2>Login with your user ID and password from alumni.harvard.edu:</h2>

<form id="signin" name="signin" action="login" method="POST">
<input type="hidden" name="authority" value="harris" />
<input type="hidden" name="url" value="{$url|escape}" />

<p><label for="username">User ID:</label>
<input type="text" id="username" name="loginUser" />
</p>

<p><label for="pwd">Password:</label>
<input type="password" id="pwd" name="loginPassword" />
</p>

<div class="signinbuttons">
	<input class="signinbutton submit" type="submit" name="login_submit" value="Sign In"/>
	<a class="signinbutton cancel" href="{$cancelURL}">Cancel</a>
</div>


</form>

<div class="clear"></div>

<div class="helptext">
<p><a href="https://post.harvard.edu/olc/pub/HAA/forgot/forgot.cgi" target="_new">Forgot your password?</a></p>
<p>If you don’t have an alumni.harvard.edu account, <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="_new">register for one now</a>! (it's free, and you’ll need it anyway to register online for many reunion events)</p>
</div>

{include file="findInclude:modules/login/templates/header.tpl"}
