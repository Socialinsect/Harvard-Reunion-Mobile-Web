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
    <p>
      <a href="{$logoutURL}" onclick="return confirmLogout()">
        <span>{if $userInfo['fullname']}Sign out{else}Sign in{/if}</span> &gt;
      </a>
    </p>
  </div>
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$banner scalable=false}

{include file="findInclude:common/templates/springboard.tpl" springboardItems=$modules springboardID="homegrid"}

<div id="social">
  <div class="links">
    <a class="facebookLink" href="{$socialInfo['facebook']['url']}">
      <div class="wrapper">{$socialInfo['facebook']['name']}</div>
    </a>
    <a class="twitterLink" href="{$socialInfo['twitter']['url']}">
      <div class="wrapper">{$socialInfo['twitter']['name']}</div>
    </a>
  </div>
  <div id="recentContainer" class="recent {$socialInfo['recent']['type']}">
    <div class="cbl"></div>
    <div class="chatbubble">
      <div id="ellipsis_0" class="message"><span id="recentMessage">{$socialInfo['recent']['message']}</span></div>
      <div id="ellipsis_1" class="info smallprint">
        <span id="recentAuthor">{$socialInfo['recent']['author']}</span>, <span id="recentAge">{$socialInfo['recent']['age']}</span>
      </div>
    </div>
    <div class="cbr"></div>
  </div>
</div>

{include file="findInclude:common/templates/footer.tpl"}
