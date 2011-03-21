{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

<div class="nonfocal">
  <h2>{$hashtag}</h2>
</div>

{foreach $posts as $i => $post}
  {capture name="title" assign="title"}
    &ldquo;{$post['message']}&rdquo; 
    <span class="smallprint"> -&nbsp;{$post['author']['name']}, {$post['when']['delta']}
    </span>
  {/capture}
  {$posts[$i]['title'] = $title}
{/foreach}

{capture name="addPostHTML" assign="addPostHTML"}
  <form method="get" action="add">
    <a class="scrolllink" name="postscrolldown"> </a>
    <textarea rows="2" name="message" placeholder="Write something..."></textarea>
    <input type="submit" value="Submit" />
    <input type="hidden" name="type" value="twitter" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
{/capture}
{$addPost = array()}
{$addPost['title'] = $addPostHTML}
{$r = array_unshift($posts, $addPost)}

{include file="findInclude:common/templates/results.tpl" results=$posts}


{include file="findInclude:common/templates/footer.tpl"}
