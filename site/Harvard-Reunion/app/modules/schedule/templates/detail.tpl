{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{$eventTitle}</h2>
  <p>{$eventDate}</p>
  {block name="bookmark"}
    {if $attending}
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
</div>

{if isset($sections['checkin'], $sections['checkin'][0])}
  {$checkedIn = count($checkins['self']) > 0}
  {$nonSelfCount = count($checkins['friends']) + count($checkins['others'])}

  {capture name="label" assign="label"}
    {block name="checkinLabel"}
      {if $checkedIn}
        <img id="fqCheckin" src="/common/images/button-foursquare-checkedin{$imageExt}" /> 
      {else}
        <img id="fqCheckin" src="/common/images/button-foursquare{$imageExt}" /> 
      {/if}
    {/block}
  {/capture}
  {$sections['checkin'][0]['label'] = $label}
  
  {capture name="title" assign="title"}
    {if $checkedIn}
      You {if $nonSelfCount}and {/if}
      {if $nonSelfCount}
        {$nonSelfCount} other {if $nonSelfCount > 1}people{else}person{/if} 
      {/if}
      are checked in
    {else}
      foursquare checkin
    {/if}
  {/capture}
  {$sections['checkin'][0]['title'] = $title}

  {if !$checkedIn && $nonSelfCount}
    {capture name="subtitle" assign="subtitle"}
      {$nonSelfCount} 
      {if $nonSelfCount > 1}people are{else}person is{/if} 
      checked in
    {/capture}
    {$sections['checkin'][0]['subtitle'] = $subtitle}
  {/if}
{/if}

{if count($sections)}
  {foreach $sections as $section}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section accessKey=false subTitleNewline=true labelColon=false}
  {/foreach}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
