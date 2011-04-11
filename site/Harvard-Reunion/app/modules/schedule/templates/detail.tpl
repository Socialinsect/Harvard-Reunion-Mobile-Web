{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{$eventTitle}</h2>
  <p>{$eventDate}</p>
  {block name="bookmark"}
    {if $registered}
      <div id="bookmarkContainer">
        <a href="javascript:void(0)" onclick="alert('Events you have registered for cannot be removed from your schedule.'); return false;"><div id="bookmark" class="on"></div></a>
      </div>
    {else}
      <div id="bookmarkContainer">
        <a href="javascript:void(0)" onclick="{if $requiresRegistration}registeredEventAlert(); {/if}toggleBookmark('{$cookieName}', '{$bookmarkItem}', {$expireDate}, '{$smarty.const.COOKIE_PATH}');">
          <div id="bookmark" class="{$bookmarkStatus}"></div>
        </a>
      </div>
    {/if}
  {/block}
  {if $fbCheckinURL || $fqCheckinURL || $fbCheckedIn || $fqCheckedIn}
    <p class="smallprint">Check in: 
      {if $fqCheckedIn}
        <span id="fqCheckin" class="checkedin">foursquare</span> 
      {elseif $fqCheckinURL}
        <a id="fqCheckin" href="{$fqCheckinURL}">foursquare</a> 
      {/if} 
      {if $fbCheckedIn}
        <span id="fbCheckin" class="checkedin">Facebook</span>
      {elseif $fbCheckinURL}
        <a id="fbCheckin" href="{$fbCheckinURL}">Facebook</a>
      {/if} 
    </p>
  {/if}
</div>

{if count($sections)}
  {foreach $sections as $section}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section accessKey=false subTitleNewline=true labelColon=false}
  {/foreach}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
