{extends file="findExtends:modules/$moduleID/templates/index.tpl"}

{block name="homeBanner"}
  <div class="banner nonfocal">
    {if $userInfo['fullname']}
      <h3>{$scheduleInfo['title']} {$scheduleInfo['year']}th Reunion</h3>
      <h3>{$scheduleInfo['dates']}</h3>
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
{/block}

{block name="social"}
  <p id="social" class="nonfocal">
    <br/>
    {$socialInfo['recent']['message']}<br />
    <span class="smallprint">- {$socialInfo['recent']['author']}, {$socialInfo['recent']['age']} via {$socialInfo['recent']['type']|capitalize}</span>
    <br/>
    <img src="/common/images/button-facebook.gif" alt="facebook group" /> 
    <a href="{$socialInfo['facebook']['url']}">{$socialInfo['facebook']['name']}</a>
    <br />
    <img src="/common/images/button-twitter.gif" alt="twitter stream" /> 
    <a href="{$socialInfo['twitter']['url']}">{$socialInfo['twitter']['name']}</a>
  </p>
{/block}
