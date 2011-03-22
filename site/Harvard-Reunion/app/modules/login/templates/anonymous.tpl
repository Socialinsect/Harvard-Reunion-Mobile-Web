{include file="findInclude:modules/login/templates/header.tpl"}

<h1>Harvard/Radcliffe Reunions</h1>

<h2>Enter anonymously by selecting your graduation year:</h2>

<form id="signin" name="signin" action="login" method="POST" onsubmit="return validateAnonymousForm()">
<input type="hidden" name="authority" value="anonymous" />
<input type="hidden" name="url" value="{$url|escape}" />

<p>
<select id="year" name="loginUser">
	<option value="" selected>Please select a year</option>
	<option value="2006">2006 (5th Reunion)</option>
	<option value="2001">2001 (10th Reunion)</option>
	<option value="1996">1996 (15th Reunion)</option>
	<option value="1991">1991 (20th Reunion)</option>
	<option value="1986">1986 (25th Reunion)</option>
	<option value="1976">1976 (35th Reunion)</option>
	<option value="1961">1961 (50th Reunion)</option>
	<option value="1956h">1956 (55th: Harvard)</option>
	<option value="1956r">1956 (55th: Radcliffe)</option>
	<option value="1951">1951 (60th Reunion)</option>
	<option value="1946h">1946 (65th: Harvard)</option>
	<option value="1946r">1946 (65th: Radcliffe)</option>
	<option value="1941">1941 (70th Reunion)</option>
</select>
</p>

<div class="signinbuttons">
	<input class="signinbutton submit" name="login_submit" type="submit" value="Enter"/>
	<a class="signinbutton cancel" href="{$cancelURL}">Cancel</a>
</div>


</form>

<div class="clear"></div>

<div class="helptext">
<p>Note: entering this app anonymously will lock you out of private areas and personalized features. Please  <a href="signin.html">sign in</a>.</p>
</div>

{include file="findInclude:modules/login/templates/footer.tpl"}
