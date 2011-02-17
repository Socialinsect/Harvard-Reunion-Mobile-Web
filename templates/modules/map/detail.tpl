{include file="findInclude:common/header.tpl" scalable=false}

{$tabBodies = array()}

{capture name="mapPane" assign="mapPane"}
    <p class="image">
      <a name="map"> </a>
      <img src="{$imageUrl}" width="{$imageWidth}" height="{$imageHeight}" alt="Map" />
    </p>
    {if $hasMap}
      <p class="scrollers">
        Scroll: 
        <a href="{$scrollNorth}">N</a>&nbsp;|&nbsp;
        <a href="{$scrollSouth}">S</a>&nbsp;|&nbsp;
        <a href="{$scrollEast}">E</a>&nbsp;|&nbsp;
        <a href="{$scrollWest}">W</a><br/>
        Zoom: 
        <a href="{$zoomInUrl}">In</a>&nbsp;|&nbsp;
        <a href="{$zoomOutUrl}">Out</a>
      </p>
    {/if}
    <div id="mapimage" style="display:none"></div>
{/capture}
{$tabBodies['map'] = $mapPane}


{capture name="photoPane" assign="photoPane"}
  {block name="photoPane"}
    <img id="loadingimage2" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
    <img id="photo" src="" width="99.9%" alt="{$name} Photo" onload="hide('loadingimage2')" />
  {/block}
{/capture}
{$tabBodies['photo'] = $photoPane}

{capture name="detailPane" assign="detailPane"}
  {block name="detailPane"}
    {if $displayDetailsAsList}
      {include file="findInclude:common/navlist.tpl" navlistItems=$details boldLabels=true accessKey=false}
    {else}
      {$details}
    {/if}
  {/block}
{/capture}
{$tabBodies['detail'] = $detailPane}

{block name="tabView"}
  <a name="scrolldown"> </a>		
	<div class="focal shaded">

		<h2>{$name}</h2>
		<p class="address">{$address|replace:' ':'&shy; '}</p>
		<a name="scrolldown"></a>		
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{include file="findInclude:common/footer.tpl"}
