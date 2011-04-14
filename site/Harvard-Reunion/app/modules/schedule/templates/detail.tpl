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
</div>

{if isset($sections['checkin'], $sections['checkin'][0])}
  {capture name="label" assign="label"}
    {block name="checkinLabel"}
      <div id="fqCheckin" class="icon {if $checkinState['checkedin']}checkedin{/if}"></div>
    {/block}
  {/capture}
  {$sections['checkin'][0]['label'] = $label}
  
  {capture name="title" assign="title"}
    {if $checkinState['checkedin']}
      You {if $checkinState['otherCount']}and {/if}
      {if $checkinState['otherCount']}
        {$checkinState['otherCount']} other {if $checkinState['otherCount'] > 1}people{else}person{/if} 
      {/if}
      are checked in
    {else}
      foursquare checkin
    {/if}
  {/capture}
  {$sections['checkin'][0]['title'] = $title}

  {if !$checkinState['checkedin'] && $checkinState['otherCount']}
    {capture name="subtitle" assign="subtitle"}
      {$checkinState['otherCount']} 
      {if $checkinState['otherCount'] > 1}people are{else}person is{/if} 
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
