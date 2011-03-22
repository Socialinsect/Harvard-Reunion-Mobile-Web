{include file="findInclude:common/templates/header.tpl"}

{capture name="postHTML" assign="postHTML"}
  <div class="photo">
    <img src="{if $photo['img']['src']}{$photo['img']['src']}{else}{$photo['thumbnail']}{/if}" />
  </div>
{/capture}
{$photo['html'] = $postHTML}

{include file="findInclude:common/templates/postDetail.tpl" post=$photo}

{include file="findInclude:common/templates/footer.tpl"}
