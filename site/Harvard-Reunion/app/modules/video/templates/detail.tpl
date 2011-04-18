{include file="findInclude:common/templates/header.tpl" scalable=false}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  {capture name="postHTML" assign="postHTML"}
    {if $video['embedHTML']}
      <div class="videoWrapper" id="videoWrapper">
        {$video['embedHTML']}
      </div>
    {else}
      <div class="nonfocal videoInfo">
        <h2>Video not available</h2>
      </div>
    {/if}
  {/capture}
  {$video['html'] = $postHTML}
  {$video['typeString'] = "video"}
  
  {include file="findInclude:common/templates/postdetail.tpl" post=$video}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
