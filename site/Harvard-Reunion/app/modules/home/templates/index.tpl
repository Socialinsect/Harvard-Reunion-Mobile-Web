{capture name="banner" assign="banner"}
  {block name="homeBanner"}
    <div class="banner nonfocal">
      <div id="ribbon">
        <span class="ribbonYear">{$scheduleInfo['year']}<span class="ribbonYearSup">th</span></span>
        <br/><span class="ribbonDesc">Reunion</span>
        <br/><span class="ribbonDate">{$scheduleInfo['dates']}</span>
      </div>
      {if $userInfo['fullname']}
        <h3>{$scheduleInfo['title']|escape} Reunion</h3>
        <h2>{$userInfo['fullname']|escape}</h2>
      {else}
        <h2>{$scheduleInfo['title']|escape}</h2>
      {/if}
      <p>
        <a href="{$logoutURL}"{if $userInfo['fullname']} onclick="return confirmLogout()"{/if}>
          <span>{if $userInfo['fullname']}Sign out{else}Sign in{/if}</span> &gt;
        </a>
      </p>
    </div>
  {/block}
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$banner scalable=false}

{include file="findInclude:common/templates/springboard.tpl" springboardItems=$modules springboardID="homegrid"}

<div class="separator"></div>

{block name="social"}
  <div id="social">
    <div class="links">
      <a class="facebookLink" href="{$socialInfo['facebook']['url']}">
        <img src="/common/images/button-facebook{$imageExt}" />{$socialInfo['facebook']['name']|escape}
      </a>
      <a class="twitterLink" href="{$socialInfo['twitter']['url']}">
        <img src="/common/images/button-twitter{$imageExt}" />{$socialInfo['twitter']['name']|escape}
      </a>
    </div>
    <div id="recentContainer" class="recent {$socialInfo['recent']['type']}">
      <div class="cbl"></div>
      <div class="chatbubble">
        <div id="ellipsis_0" class="message"><span id="recentMessage">
          {$socialInfo['recent']['message']}{* already html escaped *}
        </span></div>
        <div id="ellipsis_1" class="info smallprint">
          <span id="recentAuthor">{$socialInfo['recent']['author']|escape}</span>, <span id="recentAge">{$socialInfo['recent']['age']}</span>
        </div>
      </div>
      <div class="cbr"></div>
    </div>
  </div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
