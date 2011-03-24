{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
  {foreach $info['paragraphs'] as $paragraph}
    {$paragraph}
  {/foreach}
</div>

{foreach $info['sections'] as $section}
  <div class="nonfocal">
    <h3>{$section['title']}:</h3>
  </div>
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section['links'] subTitleNewline=true}
{/foreach}

{include file="findInclude:common/templates/footer.tpl"}
