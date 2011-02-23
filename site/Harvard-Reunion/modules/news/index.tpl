{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="newsHeader"}
  <div class="nonfocal" id="navbar2">
    Place reunion news header here
  </div>
{/block}

{block name="newsFooter"}
  {* You can put any footer stuff here *}
  {$smarty.block.parent}
{/block}
