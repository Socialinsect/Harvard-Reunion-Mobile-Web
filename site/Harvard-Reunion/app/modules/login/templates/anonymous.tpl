{include file="findInclude:common/templates/header.tpl" customHeader=''}

<div class="nonfocal">
  <h1 class="reunionHeader">Harvard/Radcliffe College&nbsp;Reunions</h1>
  <h3>Enter anonymously by selecting your graduation year:</h3>

  <form id="signin" name="signin" action="login" method="POST" onsubmit="return validateAnonymousForm()">
    <input type="hidden" name="authority" value="anonymous" />
    <input type="hidden" name="url" value="{$url|escape}" />
    <p>
      <select id="year" name="loginUser">
        <option value="" selected></option>
        {foreach $reunionYears as $config}
          {if $config['separate']}
            <option value="{$config['year']}h">{$config['year']} ({$config['number']}th Harvard)</option>
            <option value="{$config['year']}r">{$config['year']} ({$config['number']}th Radcliffe)</option>
          {else}
            <option value="{$config['year']}">{$config['year']} ({$config['number']}th Reunion)</option>
          {/if}
        {/foreach}
      </select>
    </p>
  
    {include file="findInclude:modules/{$moduleID}/templates/buttons.tpl" submitText="Enter" cancelText="Cancel"}
  </form>

  <div class="clear"></div>

  <div class="helptext">
    <p>Note: Entering this app anonymously will lock you out of private areas and personalized features. For the full reunion app experience, please <a href="index?authority=harris&url=%2Fhome%2Findex">sign in</a>.</p>
    <p>Not registered? <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="reunionHarris">Sign up</a> for an alumni login.</p>
  </div>
</div>

{include file="findInclude:common/templates/footer.tpl"}
