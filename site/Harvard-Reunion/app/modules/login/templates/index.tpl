{include file="findInclude:common/templates/header.tpl" customHeader=''}

<div class="nonfocal">

	<h1 class="reunionHeader">Harvard/Radcliffe College&nbsp;Reunions</h1>
	
	<p id="intro">Veritas Mobile: Your Reunion Connection. View your personalized schedule, maps, news, photos, and more.</p>

	<div class="ribbon"><a href="{$harrisURL}">Sign in using your alumni login<br/>
	<span class="smallprint">This unlocks private features of the website.</span></a></div>
	
	<div class="or">or</div>
	
	<div class="ribbon"><a href="{$anonymousURL}">Select your graduation year<br/>
	<span class="smallprint">Some features will be unavailable to you.</span></a></div>
	
  {if ($platform == 'iphone' || $plaform == 'ipad') && !$suppressiOSLink}
    <div id="download"><a href="http://itunes.apple.com/us/app/harvard-mobile/id389199460"><img src="/common/images/signin-appstore.png" width="124" height="46" alt="Download">Get the native app for your iPhone</a></div>
  {/if}

</div>

{include file="findInclude:common/templates/footer.tpl"}
