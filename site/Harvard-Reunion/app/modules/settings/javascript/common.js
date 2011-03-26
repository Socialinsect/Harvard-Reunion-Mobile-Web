function settingChanged(input) {
  var value = input.checked ? '1' : '0';
  window.location = CHANGE_SETTIINGS_URL+input.name+'='+value;
}
