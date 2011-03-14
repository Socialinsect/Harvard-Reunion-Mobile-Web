{capture name="banner" assign="banner"}
  <div class="banner nonfocal">
    <div id="ribbon">
      <span class="ribbonYear">{$scheduleInfo['year']}<span class="ribbonYearSup">th</span></span>
      <br/><span class="ribbonDesc">Reunion</span>
      <br/><span class="ribbonDate">{$scheduleInfo['dates']}</span>
    </div>
    <h3>Harvard College Reunion</h3>
    <h2>{$userInfo['fullname']}</h2>
    <p>Class of {$userInfo['class']}</p>
    <p><a href="#">Sign out</a> | <a href="/settings/">Settings</a></p>
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
  <div class="recent {$socialInfo['recent']['type']}">
    <div class="cbl"></div>
    <div class="chatbubble">
      {$socialInfo['recent']['message']}
      <div class="info smallprint">{$socialInfo['recent']['author']}, {$socialInfo['recent']['age']} ago</div>
    </div>
    <div class="cbr"></div>
  </div>
</div>

{include file="findInclude:common/templates/footer.tpl"}
