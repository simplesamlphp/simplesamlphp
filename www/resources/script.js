/**
 * Set focus to the element with the given id.
 *
 * @param id  The id of the element which should receive focus.
 */
function SimpleSAML_focus(id) {
  element = document.getElementById(id);
  if(element != null) {
    element.focus();
  }
}
