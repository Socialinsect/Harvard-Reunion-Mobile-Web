{include file="findInclude:common/templates/header.tpl" customHeader=''}

<div class="nonfocal">

	<h1>Harvard/Radcliffe College Reunions</h1>

	{if $authFailed}
		<p>We're sorry, but there was a problem with your login. Please check your user ID and password (the same login you use at alumni.harvard.edu) and try again.</p>
	{else}
		<h3>Sign in with your user ID and password from alumni.harvard.edu:</h3>
	{/if}

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
		<p>Not registered?  <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="_new">Sign up</a> for an alumni login.</p>
		<p><a href="https://post.harvard.edu/olc/pub/HAA/forgot/forgot.cgi" target="_new">Forgot password</a></p>
	</div>
	
</div>

{include file="findInclude:modules/login/templates/footer.tpl"}
