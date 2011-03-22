{include file="findInclude:modules/login/templates/header.tpl"}

<h1>Harvard/Radcliffe Reunions</h1>

<h2>Sign in with your alumni user ID and password:</h2>

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
<p><a href="https://post.harvard.edu/olc/pub/HAA/forgot/forgot.cgi" target="_new">Forgot password</a></p>
<p>Don’t have an alumni.harvard.edu account? <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="_new">Register for one now</a>!</p>
</div>

{include file="findInclude:modules/login/templates/footer.tpl"}
