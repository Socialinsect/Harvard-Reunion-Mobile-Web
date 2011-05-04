<div class="focal">
  {if count($posts)}
    {foreach $posts as $i => $post}
      {$lastPost = $post@last}
      {block name="postContent"}
        <div class="comment">
          <img class="profilepic" src="{$post['author']['photo']}" /> 
          <div class="wrapper">
            &ldquo;{$post['message']|escape}&rdquo; 
            <span class="smallprint"> -&nbsp;{$post['author']['name']|escape}, {$post['when']['shortDelta']}</span>
          </div>
        </div>
      {/block}
    {/foreach}
  {else}
    No posts for {$groupName}
  {/if}
</div>
