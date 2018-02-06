<?php

function admin_rooms_title() {
  return _("Rooms");
}

function admin_rooms() {
  global $user;

  $event_source = sql_select("SELECT * FROM `Events` ORDER BY `name`");
  $events = array();
  foreach($event_source as $event) {
    $events[$event['event_id']] = $event['name'];
  }
  $event_id = " ";

  $rooms_source = Room_by_name();
  $rooms = array();
  foreach ($rooms_source as $room)
    $rooms[] = array(
        'name' => $room['Name'],
        'from_pentabarf' => $room['FromPentabarf'] == '1' ? '&#10003;' : '',
        'public' => $room['show'] == '1' ? '&#10003;' : '',
        'e_id' => $events[$room['e_id']],
        'actions' => buttons(array(
            button(page_link_to('admin_rooms') . '&show=edit&id=' . $room['RID'], _("edit"), 'btn-xs'),
            button(page_link_to('admin_rooms') . '&show=delete&id=' . $room['RID'], _("delete"), 'btn-xs')
        ))
    );
  $room = null;

  if (isset($_REQUEST['show'])) {
    $msg = "";
    $name = "";
    $from_pentabarf = "";
    $public = '1';
    $number = "";

    $angeltypes_source = AngelTypes();
    $angeltypes = array();
    $angeltypes_count = array();
    foreach ($angeltypes_source as $angeltype) {
      $angeltypes[$angeltype['id']] = $angeltype['name'];
      $angeltypes_count[$angeltype['id']] = 0;
    }

    if (test_request_int('id')) {
      $room = Room_by_id($_REQUEST['id']);
      if (count($room) > 0) {
        $id = $_REQUEST['id'];
        $name = $room[0]['Name'];
        $from_pentabarf = $room[0]['FromPentabarf'];
        $public = $room[0]['show'];
        $number = $room[0]['Number'];
        $needed_angeltypes = NeededAngelTypes_by_room($id);
        foreach ($needed_angeltypes as $needed_angeltype)
          $angeltypes_count[$needed_angeltype['angel_type_id']] = $needed_angeltype['count'];
      } else
        redirect(page_link_to('admin_rooms'));
    }

    if ($_REQUEST['show'] == 'edit') {
      if (isset($_REQUEST['submit'])) {
        $ok = true;

        if (isset($_REQUEST['name']) && strlen(strip_request_item('name')) > 0) {
          $name = strip_request_item('name');
          if (isset($room) && count_room_by_id_name($name, $id) > 0) {
            $ok = false;
            $msg .= error(_("This name is already in use."), true);
          }
        } else {
          $ok = false;
          $msg .= error(_("Please enter a name."), true);
        }

        if (isset($_REQUEST['from_pentabarf']))
          $from_pentabarf = '1';
        else
          $from_pentabarf = '';

        if (isset($_REQUEST['public']))
          $public = '1';
        else
          $public = '';

        if (isset($_REQUEST['number']))
          $number = strip_request_item('number');
        else
          $ok = false;

        if (isset($_REQUEST['event_id'])) {
          $event_id = event($_REQUEST['event_id']);

          if ($event_id === false)
            engelsystem_error('Unable to load event type.');
          if ($event_id == null) {
            $ok = false;
            error(_('Please select an Event type.'));
          } else
              $event_id = $_REQUEST['event_id'];
        }

        foreach ($angeltypes as $angeltype_id => $angeltype) {
          if (isset($_REQUEST['angeltype_count_' . $angeltype_id]) && preg_match("/^[0-9]{1,4}$/", $_REQUEST['angeltype_count_' . $angeltype_id]))
            $angeltypes_count[$angeltype_id] = $_REQUEST['angeltype_count_' . $angeltype_id];
          else {
            $ok = false;
            $msg .= error(sprintf(_("Please enter needed angels for type %s.", $angeltype)), true);
          }
        }

        if ($ok) {
          if (isset($id)) {
            update_rooms($name, $from_pentabarf, $public, $number, $id, $event_id);
            engelsystem_log("Room updated: " . $name . ", pentabarf import: " . $from_pentabarf . ", public: " . $public . ", number: " . $number . ", event: " . $event_id);
          } else {
            $id = Room_create($name, $from_pentabarf, $public, $number, $event_id);
            if ($id === false)
              engelsystem_error("Unable to create room.");
            engelsystem_log("Room created: " . $name . ", pentabarf import: " . $from_pentabarf . ", public: " . $public . ", number: " . $number . ", event: " . $event_id);
          }

          delete_NeededAngelTypes_by_id($id);
          $needed_angeltype_info = array();
          foreach ($angeltypes_count as $angeltype_id => $angeltype_count) {
            $angeltype = AngelType($angeltype_id);
            if ($angeltype === false)
              engelsystem_error("Unable to load angeltype.");
            if ($angeltype != null) {
             insert_by_room($id, $angeltype_id, $angeltype_count);
             $needed_angeltype_info[] = $angeltype['name'] . ": " . $angeltype_count;
            }
          }

          engelsystem_log("Set needed angeltypes of room " . $name . " to: " . join(", ", $needed_angeltype_info));
          success(_("Room saved."));
          redirect(page_link_to("admin_rooms"));
        }
      }
      $angeltypes_count_form = array();
      foreach ($angeltypes as $angeltype_id => $angeltype)
        $angeltypes_count_form[] = div('col-lg-4 col-md-6 col-xs-6', array(
            form_spinner('angeltype_count_' . $angeltype_id, $angeltype, $angeltypes_count[$angeltype_id])
        ));

      return page_with_title(admin_rooms_title(), array(
          buttons(array(
              button(page_link_to('admin_rooms'), _("back"), 'back')
          )),
          $msg,
          form(array(
              div('row', array(
                  div('col-md-6', array(
                      form_text('name', _("Name"), $name),
                      form_checkbox('from_pentabarf', _("Frab import"), $from_pentabarf),
                      form_checkbox('public', _("Public"), $public),
                      form_text('number', _("Room number"), $number),
                      form_select('event_id', _("Event Name"), $events, $event_id)
                  )),
                  div('col-md-6', array(
                      div('row', array(
                          div('col-md-12', array(
                              form_info(_("Needed angels:"))
                          )),
                          join($angeltypes_count_form)
                      ))
                  ))
              )),
              form_submit('submit', _("Save"))
          ))
      ));
    } elseif ($_REQUEST['show'] == 'delete') {
      if (isset($_REQUEST['ack'])) {
        if (! Room_delete($id))
          engelsystem_error("Unable to delete room.");

        engelsystem_log("Room deleted: " . $name);
        success(sprintf(_("Room %s deleted."), $name));
        redirect(page_link_to('admin_rooms'));
      }

      return page_with_title(admin_rooms_title(), array(
          buttons(array(
              button(page_link_to('admin_rooms'), _("back"), 'back')
          )),
          sprintf(_("Do you want to delete room %s?"), $name),
          buttons(array(
              button(page_link_to('admin_rooms') . '&show=delete&id=' . $id . '&ack', _("Delete"), 'delete')
          ))
      ));
    }
  }

  return page_with_title(admin_rooms_title(), array(
      buttons(array(
          button(page_link_to('admin_rooms') . '&show=edit', _("add"))
      )),
      msg(),
      table(array(
          'name' => _("Name"),
          'from_pentabarf' => _("Frab import"),
          'public' => _("Public"),
          'e_id' => _("Event"),
          'actions' => ""
      ), $rooms)
  ));
}
?>
