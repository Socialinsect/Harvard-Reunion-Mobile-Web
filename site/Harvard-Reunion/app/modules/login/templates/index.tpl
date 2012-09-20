{include file="findInclude:common/templates/header.tpl" customHeader=''}

  <div class="nonfocal">

    {include file="findInclude:modules/{$moduleID}/templates/banner.tpl"}

    <p id="intro">Thank you for downloading Harvard's Mobile Reunion app. This application is no longer supported. Please visit our mobile-friendly <a href="http://alumni.harvard.edu/college/reunions-events">Harvard College Reunion</a> page for information about your reunion.</p>
  
  {block name="ribbons"}
    <div class="ribbon"><a href="http://alumni.harvard.edu/college/reunions-events">Visit the Harvard College Reunion page

    {if $tabletDisplay}
       <br/>
       <span class="smallprint">http://alumni.harvard.edu/college/reunions-events</span>     
    {/if}
    </a></div>
  {/block}  

{include file="findInclude:common/templates/footer.tpl"}
