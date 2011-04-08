{extends file="findExtends:modules/$moduleID/templates/index.tpl"}

{block name="photo"}
  <p class="photolist">
    <a href="{$photo['url']}">
      <img class="thumbnail" src="{$photo['thumbnail']}" />
      <br/>
      <span class="when">{$photo['when']['delta']}</span>
    </a>
  </p>
{/block}
