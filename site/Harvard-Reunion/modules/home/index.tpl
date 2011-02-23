{capture name="banner" assign="banner"}
  <div class="banner nonfocal">
    <div id="ribbon">
      <span class="ribbonYear">{$reunionInfo['year']}</span><br/>
      Reunion<br/>
      <span class="ribbonDate">{$reunionInfo['dates']}</span>
    </div>
    <h3>Harvard College Reunion</h3>
    <h2>{$attendee}</h2>
    <p>
      Class of 2001<br/>
      <span class="smallprint"><a href="#">Sign out</a> | <a href="/settings/">Settings</a></span>
    </p>
  </div>
{/capture}

{include file="findInclude:common/header.tpl" customHeader=$banner scalable=false}

{include file="findInclude:common/springboard.tpl" springboardItems=$modules springboardID="homegrid"}

<div id="social">
  <div class="links">
    <a class="facebookLink" href="{$facebookGroup['url']}"><div class="wrapper">{$facebookGroup['name']}</div></a>
    <a class="twitterLink" href="{$twitterTag['url']}"><div class="wrapper">{$twitterTag['name']}</div></a>
  </div>
  <div class="recent twitter">
    <div class="chatbubble">
      {$recentPost['message']}
      <div class="info smallprint">{$recentPost['author']} - {$recentPost['time']}</div>
    </div>
    <div class="chatarrow">&lt;</div>
  </div>
</div>

{include file="findInclude:common/footer.tpl"}
