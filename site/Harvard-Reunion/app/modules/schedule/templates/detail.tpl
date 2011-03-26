{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  {if $registered}
    <div id="bookmarkContainer">
      <a href="javascript:void(0)" onclick="alert('Events you have registered for cannot be removed from your schedule.'); return false;"><div id="bookmark" class="on"></div></a>
    </div>
  {else}
    {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$eventId exdate="COOKIE_DURATION" path="COOKIE_PATH"}
  {/if}
  <h2>{$eventTitle}</h2>
  {$eventDate}
</div>

{if count($sections)}
  {foreach $sections as $section}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section accessKey=false subTitleNewline=true}
  {/foreach}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
