{capture name="banner" assign="banner"}
  <div class="banner nonfocal">
    <div id="ribbon">
      <span class="ribbonYear">{$scheduleInfo['year']}<span class="ribbonYearSup">th</span></span>
      <br/><span class="ribbonDesc">Reunion</span>
      <br/><span class="ribbonDate">{$scheduleInfo['dates']}</span>
    </div>
    {if $userInfo['fullname']}
      <h3>{$scheduleInfo['title']} Reunion</h3>
      <h2>{$userInfo['fullname']}</h2>
    {else}
      <h2>{$scheduleInfo['title']}</h2>
    {/if}
    <p><a href="/login/logout?authority={$userInfo['authority']}" onclick="return confirmLogout()">Sign out &gt;</a></p>
  </div>
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$banner scalable=false}

{include file="findInclude:common/templates/springboard.tpl" springboardItems=$modules springboardID="homegrid"}

<script type="text/javascript">
	function confirmLogout() {
		return (confirm("Are you sure you want to sign out? Events you\'ve bookmarked in this website may be forgotten.")) 
	}
</script>

<div id="social">
  <div class="links">
    <a class="facebookLink" href="{$socialInfo['facebook']['url']}">
      <div class="wrapper">{$socialInfo['facebook']['name']}</div>
    </a>
    <a class="twitterLink" href="{$socialInfo['twitter']['url']}">
      <div class="wrapper">{$socialInfo['twitter']['name']}</div>
    </a>
  </div>
  <div class="recent {$socialInfo['recent']['type']}">
    <div class="cbl"></div>
    <div class="chatbubble">
      <div id="ellipsis_0" class="message">{$socialInfo['recent']['message']}</div>
      <div id="ellipsis_1" class="info smallprint">{$socialInfo['recent']['author']}, {$socialInfo['recent']['age']}</div>
    </div>
    <div class="cbr"></div>
  </div>
</div>

{include file="findInclude:common/templates/footer.tpl"}
