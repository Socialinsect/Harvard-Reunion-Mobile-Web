{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>About Your Reunion</h2>
</div>
<div class="focal">
  {foreach $info['about'] as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div>

<div class="nonfocal">
  <h2>Helpful Links</h2>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$info['links'] subTitleNewline=true}

{include file="findInclude:common/templates/footer.tpl"}
