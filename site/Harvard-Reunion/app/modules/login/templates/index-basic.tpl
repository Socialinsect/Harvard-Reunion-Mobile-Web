{extends file="findExtends:modules/$moduleID/templates/index.tpl"}

{block name="ribbons"}
	<p class="ribbon"><a href="{$harrisURL}">Sign in using your alumni login</a><br/>
	<span class="smallprint">This unlocks private features of the website.</span></p>
	
	<p class="or">or</p>
	
	<p class="ribbon"><a href="{$anonymousURL}">Select your graduation year</a><br/>
	<span class="smallprint">Some features will be unavailable to you.</span></p>
{/block}
