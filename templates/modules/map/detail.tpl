{include file="findInclude:common/header.tpl" scalable=false}

{$tabBodies = array()}

{capture name="mapPane" assign="mapPane"}
  {block name="mapImage"}
  <p class="image">
    <a name="map"> </a>
    {if $isStatic}
      {include file="findInclude:modules/map/mapscrollers.tpl"}
    {/if}
    <img id="staticmapimage" onload="hide('loadingimage')" src="{$imageUrl}" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
  </p>
  <div id="mapimage" style="display:none"></div>
  {/block}
  {if $hasMap}
    {include file="findInclude:modules/map/mapcontrols.tpl"}
  {/if}
{/capture}
{$tabBodies['map'] = $mapPane}

{capture name="detailPane" assign="detailPane"}
  {block name="photoPane"}
    {if $photo}
      <img id="loadingimage2" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
      <img id="photo" src="" width="99.9%" alt="{$name} Photo" onload="hide('loadingimage2')" />
    {/if}
  {/block}
  {block name="detailPane"}
    {if $displayDetailsAsList}
      {include file="findInclude:common/navlist.tpl" navlistItems=$details boldLabels=true accessKey=false}
    {else}
      {$details}
    {/if}
  {/block}
{/capture}
{$tabBodies['info'] = $detailPane}

{if $hasNearby}
  {capture name="nearbyPane" assign="nearbyPane"}
    {include file="findInclude:common/navlist.tpl" navlistItems=$nearbyResults boldLabels=true accessKey=false}
  {/capture}
  {$tabBodies['nearby'] = $nearbyPane}
{/if}

{block name="tabView"}
  <a name="scrolldown"> </a>
    <div class="focal shaded">
        <h2>{$name}</h2>
        <p class="address">{$address|replace:' ':'&shy; '}</p>
        {include file="findInclude:common/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
        <a name="scrolldown"></a>
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{include file="findInclude:common/footer.tpl"}
