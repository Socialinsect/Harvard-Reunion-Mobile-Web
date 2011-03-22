{include file="findInclude:common/templates/header.tpl"}

{capture name="postHTML" assign="postHTML"}
  {if $video['embedHTML']}
    <div class="video">
      {$video['embedHTML']}
    </div>
  {else}
    <div class="nonfocal videoInfo">
      <h2>Video not available</h2>
    </div>
  {/if}
{/capture}
{$video['html'] = $postHTML}

{include file="findInclude:common/templates/postDetail.tpl" post=$video}

{include file="findInclude:common/templates/footer.tpl"}
