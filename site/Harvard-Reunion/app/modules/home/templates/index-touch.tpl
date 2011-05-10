{extends file="findExtends:modules/$moduleID/templates/index.tpl"}

{block name="homeBanner"}
  <div class="banner nonfocal">
    {if $userInfo['fullname']}
      <h3>
        {$scheduleInfo['title']}<br/>
        {$scheduleInfo['year']}th Reunion<br/>
        {$scheduleInfo['dates']}
      </h3>
      <h2>{$userInfo['fullname']}</h2>
    {else}
      <h2>
        {$scheduleInfo['title']}<br/>
        {$scheduleInfo['year']}th Reunion<br/>
        {$scheduleInfo['dates']}
      </h2>
    {/if}
    <p>
      <a href="{$logoutURL}" onclick="return confirmLogout()">
        {if $userInfo['fullname']}Sign out{else}Sign in{/if} &gt;
      </a>
    </p>
  </div>
{/block}

{block name="social"}
  {if $socialInfo['recent']}
    <table id="social" border="1" cellpadding="4"><tr><td>
      {$socialInfo['recent']['message']|escape}<br />
      <span class="smallprint">
        - {$socialInfo['recent']['author']|escape}, {$socialInfo['recent']['age']} via {$socialInfo['recent']['type']|capitalize}
      </span>
    </td></tr></table>
  {/if}
  <p class="nonfocal">
    <img src="/common/images/button-facebook.gif" alt="facebook group" /> 
    <a href="{$socialInfo['facebook']['url']}">{$socialInfo['facebook']['name']}</a>
    <br />
    <img src="/common/images/button-twitter.gif" alt="twitter stream" /> 
    <a href="{$socialInfo['twitter']['url']}">{$socialInfo['twitter']['name']}</a>
  </p>
{/block}
