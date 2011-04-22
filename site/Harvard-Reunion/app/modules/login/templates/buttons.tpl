<p class="signinbuttons">
  <input class="signinbutton submit" name="login_submit" type="submit" value="{$submitText}"/>
  {if $hasCancel|default: true}
    <input class="signinbutton cancel" name="login_cancel" type="submit" value="{$cancelText}" onclick="formCancelButtonPressed=true;"/>
  {/if}
</p>
