{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$eventId exdate="COOKIE_DURATION" path="COOKIE_PATH"}
  <h2>{$eventTitle}</h2>
  {$eventDate}
</div>

{if count($sections)}
  {foreach $sections as $section}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section accessKey=false subTitleNewline=true}
  {/foreach}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
