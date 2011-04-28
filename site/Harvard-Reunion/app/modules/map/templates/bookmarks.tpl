{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" placeholder="Search Map" tip=$searchTip}

{if $groups}
  <div class="nonfocal searchHeader">{$groupAlias}</div>
  {include file="findInclude:common/templates/results.tpl" results=$campuses subTitleNewline=true}
{/if}

{if $places}
  <div class="nonfocal searchHeader">Places</div>
  {include file="findInclude:common/templates/results.tpl" results=$places subTitleNewline=true}
{/if}

{if $events}
  <div class="nonfocal searchHeader">Events</div>
  {include file="findInclude:common/templates/results.tpl" results=$events subTitleNewline=true}
{/if}

{if !$groups && !$places && !$events}
  <div class="nonfocal">You do not have any bookmarks</div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
