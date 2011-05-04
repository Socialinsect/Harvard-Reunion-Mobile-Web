<div class="focal">
  {if count($posts)}
    {foreach $posts as $i => $post}
      {$lastPost = $post@last}
      {block name="tweetContent"}
        <div class="comment">
          <img class="profilepic" src="{$post['author']['photo']}" /> 
          <div class="wrapper">
            &ldquo;{$post['message']}&rdquo; 
            <span class="smallprint"> - {$post['author']['name']}, {$post['when']['shortDelta']}</span>
          </div>
        </div>
      {/block}
    {/foreach}
  {else}
    No tweets for {$hashtag}
  {/if}
</div>
