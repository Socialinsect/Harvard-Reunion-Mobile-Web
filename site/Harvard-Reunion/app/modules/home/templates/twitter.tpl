{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

<div class="nonfocal">
  <a class="tweetButton" href="{$tweetURL}"><span class="tweetLink">tweet</span></a>
  <h2>{$hashtag}</h2>
</div>

{foreach $posts as $i => $post}
  {capture name="title" assign="title"}
    &ldquo;{$post['message']}&rdquo; 
    <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  {/capture}
  {$posts[$i]['title'] = $title}
{/foreach}

{if !count($posts)}
  {$empty = array()}
  {$empty['title'] = 'No tweets for '|cat:$hashtag}
  {$posts[] = $empty}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$posts navlistID="listContainer"}

<div class="nonfocal">
  <span class="smallprint">View tweets for {$hashtag} at <a href="{$twitterURL}">twitter.com</a></span>
</div>

{include file="findInclude:common/templates/footer.tpl"}
