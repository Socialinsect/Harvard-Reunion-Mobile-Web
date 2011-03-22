{include file="findInclude:common/templates/header.tpl"}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
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
{/if}

{include file="findInclude:common/templates/footer.tpl"}
