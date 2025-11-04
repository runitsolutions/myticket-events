<table class="kp-table">
    <thead>
        <tr>
            <th><?php echo esc_html__('Ticket', 'myticket-events'); ?></th>
            <th><?php echo esc_html__('Row', 'myticket-events'); ?></th>                                                   <!-- column hidden for mobile screens -->
            <th><?php echo esc_html__('Price', 'myticket-events'); ?></th>                                                 <!-- column hidden for mobile screens -->
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody class="kp-ticket-row">
        <tr>
            <td>{{ticket_text}} / <b> {{ticket_zone_title}}</b>
                <span class="m-row"><?php echo esc_html__('Row', 'myticket-events'); ?> <b>{{ticket_row}}</b></span>       <!-- row shown for mobile screens only -->
                <span class="m-row"><?php echo esc_html__('Price', 'myticket-events'); ?> <b>{{ticket_price}}</b></span>   <!-- row shown for mobile screens only -->
            </td>
            <td>{{ticket_row}}</td>                                                                                        <!-- column hidden for mobile screens -->
            <td>{{ticket_price}} <span><?php echo esc_html__('per ticket', 'myticket-events'); ?></span></td>              <!-- column hidden for mobile screens -->
            <td data-zone="{{ticket_zone_id}}" data-index="{{ticket_id}}" class="kp-rem-seat">&times;</td>
        </tr>
    </tbody>
</table>