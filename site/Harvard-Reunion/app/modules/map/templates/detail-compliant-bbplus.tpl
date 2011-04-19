{extends file="findExtends:modules/$moduleID/templates/detail.tpl"}

{block name="mapImage"}
<p class="image">
  <a name="map"> </a>
  <img id="staticmapimage" src="{$imageUrl}" alt="Map" />
</p>
<p><a href="{$imageUrl}">{$imageUrl}</a></p>
{/block}

{block name="photoPane"}
  <p class="image">
    <img src="{$photoURL}" width="{$photoWidth}" alt="Photo" />
  </p>
{/block}
