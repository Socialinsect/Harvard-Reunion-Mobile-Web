{include file="findInclude:common/templates/header.tpl" customHeader=''}

  <div class="nonfocal">

    {include file="findInclude:modules/{$moduleID}/templates/banner.tpl"}

{if !$tabletDisplay}
  
    <p id="intro">Veritas Mobile: Your Reunion Connection. View your personalized schedule, maps, news, photos, and more.</p>
  
  {block name="ribbons"}
    <div class="ribbon"><a href="{$harrisURL}">Sign in using your alumni login<br/>
    <span class="smallprint">This unlocks private features of the {if $isNative}app{else}website{/if}.</span></a></div>
    
    <div class="or">or</div>
    
    <div class="ribbon"><a href="{$anonymousURL}">Select your graduation year<br/>
    <span class="smallprint">Some features will be unavailable to you.</span></a></div>
  {/block}
    
{else}
  </div>
  
  <div class="loginBlock nonfocal">
    <div class="columns">
      <div class="harris">
        <h3>Sign in with your alumni login</h3>
        <p class="smallprint">
          {if $authFailed}
            We're sorry, but there was a problem with your login. Please check your user ID and password (the same login you use at alumni.harvard.edu) and try again.
          {else}
            This unlocks private features of the application.
          {/if}
        </p>
      
        <form id="signin" name="signin" action="login" method="POST" onsubmit="return validateHarrisForm();">
          {foreach $defaultArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
          <input type="hidden" name="authority" value="harris" />
        
          {block name="inputs"}
            <p><label for="username">User ID:</label>
            <input type="text" id="username" name="loginUser" />
            </p>
        
            <p><label for="pwd">Password:</label>
            <input type="password" id="pwd" name="loginPassword" />
            </p>
          {/block}
          
          {include file="findInclude:modules/{$moduleID}/templates/buttons.tpl" submitText="Sign In" hasCancel=false}
        </form>
      
        <div class="clear"></div>
      </div>
      
      <div class="or">or</div>
      
      <div class="anonymous">
        <h3>Just select your graduation year</h3>
        <p class="smallprint">Entering this app anonymously will lock you out of private areas and personalized features.</p>
    
        <form id="signin" name="signin" action="login" method="POST" onsubmit="return validateAnonymousForm();">
          {foreach $defaultArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
          <input type="hidden" name="authority" value="anonymous" />
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
        
          {include file="findInclude:modules/{$moduleID}/templates/buttons.tpl" submitText="Enter" hasCancel=false}
        </form>
      
        <div class="clear"></div>
      </div>
    </div>
  
    <div class="helptext">
      <p>Not registered?  <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="reunionHarris">Sign up</a> for an alumni login.</p>
      <p><a href="https://post.harvard.edu/olc/pub/HAA/forgot/forgot.cgi" target="reunionHarris">Forgot password</a></p>
    </div>
  </div>
  
  <div class="nonfocal">
{/if}

    {if ($platform == 'iphone' || $plaform == 'ipad') && !$suppressiOSLink}
      <div id="download"><a href="#{*http://itunes.apple.com/us/app/harvard-mobile/id389199460*}"><img src="/common/images/signin-appstore.png" width="124" height="46" alt="Download">Coming Soon! Get the native app for your {if $tabletDisplay}iPad{else}iPhone{/if}</a></div>
    {/if}
  
  </div>

{include file="findInclude:common/templates/footer.tpl"}
