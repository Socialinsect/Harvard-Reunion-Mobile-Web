{include file="findInclude:common/templates/header.tpl" customHeader=''}

<div class="nonfocal">
	<h1>Harvard/Radcliffe College Reunions</h1>
	<h2>Enter anonymously by selecting your graduation year:</h2>

	<form id="signin" name="signin" action="login" method="POST" onsubmit="return validateAnonymousForm()">
		<input type="hidden" name="authority" value="anonymous" />
		<input type="hidden" name="url" value="{$url|escape}" />
		<p>
			<select id="year" name="loginUser">
				<option value="" selected>Please select a year</option>
				{foreach $reunionYears as $config}
					{if $config['separate']}
						<option value="{$config['year']}h">{$config['year']} ({$config['number']}th Harvard)</option>
						<option value="{$config['year']}r">{$config['year']} ({$config['number']}th Radcliffe)</option>
					{else}
						<option value="{$config['year']}">{$config['year']} ({$config['number']}th Reunion)</option>
					{/if}
				{/foreach}
			</select>
		</p>
	
		<div class="signinbuttons">
			<input class="signinbutton submit" name="login_submit" type="submit" value="Enter"/>
			<a class="signinbutton cancel" href="{$cancelURL}">Cancel</a>
		</div>
	</form>

	<div class="clear"></div>

	<div class="helptext">
		<p>Note: entering this app anonymously will lock you out of private areas and personalized features. For the full reunion app experience, please <a href="signin.html">sign in</a>.</p>
		<p>Not registered? <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="_new">Sign up</a> for an alumni login.</p>
	</div>
</div>

{include file="findInclude:modules/login/templates/footer.tpl"}
