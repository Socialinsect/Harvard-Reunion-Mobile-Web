{include file="findInclude:modules/login/templates/header.tpl"}

<h1>Harvard College Reunions</h1>

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
	<option value="1956">1956 (55th Reunion)</option>
	<option value="1951">1951 (60th Reunion)</option>
	<option value="1946">1946 (65th Reunion)</option>
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
<p>Note: entering this way will allow you to view and bookmark events, maps, and more, but will lock you out of private areas and personalized features of the site. Please <a href="signin.html">log in</a> to unlock these features.</p>
<p>If you don’t have an alumni.harvard.edu account, <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="_new">register for one now</a>! (it's free, and you’ll need it anyway to register online for many reunion events)</p>
</div>

{include file="findInclude:modules/login/templates/footer.tpl"}
