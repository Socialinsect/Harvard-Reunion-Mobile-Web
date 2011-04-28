{include file="findInclude:common/templates/header.tpl"}

{if $groups}
  <div class="nonfocal bookmarkHeader">{$groupAlias}</div>
  {include file="findInclude:common/templates/results.tpl" results=$campuses subTitleNewline=true}
{/if}

{if $places}
  <div class="nonfocal bookmarkHeader">Places</div>
  {include file="findInclude:common/templates/results.tpl" results=$places subTitleNewline=true}
{/if}

{if $events}
  <div class="nonfocal bookmarkHeader">Events</div>
  {include file="findInclude:common/templates/results.tpl" results=$events subTitleNewline=true}
{/if}

{if !$groups && !$places && !$events}
  <div class="nonfocal">You do not have any bookmarks</div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
