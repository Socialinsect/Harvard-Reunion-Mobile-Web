{$pageTitle = "$moduleName Help"}
{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
  {foreach $moduleStrings.help as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div>

{include file="findInclude:common/templates/footer.tpl"}
