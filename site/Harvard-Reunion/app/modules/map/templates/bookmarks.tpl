{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" placeholder="Search Map" tip=$searchTip}

{if $groups}
<div class="nonfocal">
  <a name="groups"> </a>
  <h3>{$groupAlias}</h3>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$campuses subTitleNewline=true}
{/if}

{if $places}
<div class="nonfocal">
  <a name="places"> </a>
  <h3>Places</h3>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$places subTitleNewline=true}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
