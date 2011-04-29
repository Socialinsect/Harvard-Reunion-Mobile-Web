<div class="focal">
  {if count($posts)}
    {foreach $posts as $i => $post}
      {$lastPost = $post@last}
      {block name="tweetContent"}
        <div class="comment">
          &ldquo;{$post['message']}&rdquo; 
          <span class="smallprint"> - {$post['author']['name']}, {$post['when']['shortDelta']}</span>
        </div>
      {/block}
    {/foreach}
  {else}
    No tweets for {$hashtag}
  {/if}
</div>
