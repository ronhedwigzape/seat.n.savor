<?php

require_once 'User.php';

class Restaurateur extends User
{

    protected $table_notifications = 'notifications';

    /***************************************************************************
     * Restaurateur constructor
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username = '', $password = '')
    {
        parent::__construct($username, $password, 'restaurateur');
    }


    /***************************************************************************
     * Execute find
     *
     * @param $stmt
     * @return Restaurateur|false
     */
    private static function executeFind($stmt)
    {
        $stmt->execute();
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc())
            return new Restaurateur($row['username'], $row['password']);
        else
            return false;
    }


    /***************************************************************************
     * Find restaurateur by id
     *
     * @param int $id
     * @return Restaurateur|boolean
     */
    public static function findById($id)
    {
        $restaurateur = new Restaurateur();
        $stmt = $restaurateur->conn->prepare("SELECT username, password FROM $restaurateur->table WHERE id = ?");
        $stmt->bind_param("i", $id);
        return self::executeFind($stmt);
    }


    /***************************************************************************
     * Convert restaurateur object to array
     *
     * @param array $append
     * @return array
     */
    public function toArray($append = [])
    {
        return parent::toArray($append);
    }


    /***************************************************************************
     * Get all restaurateurs as array of objects
     *
     * @return Restaurateur[]
     */
    public static function all()
    {
        $restaurateur = new Restaurateur();
        $sql = "SELECT username, password FROM $restaurateur->table ORDER BY id";
        $stmt = $restaurateur->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();
        $restaurateurs = [];
        while($row = $result->fetch_assoc()) {
            $restaurateurs[] = new Restaurateur($row['username'], $row['password']);
        }
        return $restaurateurs;
    }


    /***************************************************************************
     * Get all restaurateurs as array of arrays
     *
     * @return array
     */
    public static function rows()
    {
        $restaurateurs = [];
        foreach(self::all() as $restaurateur) {
            $restaurateurs[] = $restaurateur->toArray();
        }
        return $restaurateurs;
    }


    /***************************************************************************
     * Check if restaurateur id exists
     *
     * @param int $id
     * @return bool
     */
    public static function exists($id)
    {
        if(!$id)
            return false;

        return (self::findById($id) != false);
    }


    /***************************************************************************
     * Check if restaurateur username exists
     *
     * @param string $username
     * @param int $id
     * @return bool
     */
    public static function usernameExists($username, $id = 0)
    {
        $restaurateur = new Restaurateur();
        $stmt = $restaurateur->conn->prepare("SELECT id FROM $restaurateur->table WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result->num_rows > 0);
    }


    /***************************************************************************
     * Insert restaurateur
     *
     * @return void
     */
    public function insert()
    {
        // check id
        if(self::exists($this->id))
            App::returnError('HTTP/1.1 409', 'Insert Error: restaurateur [id = ' . $this->id . '] already exists.');

        // check username
        if(trim($this->username) == '')
            App::returnError('HTTP/1.1 422', 'Insert Error: restaurateur username is required.');
        else if(self::usernameExists($this->username))
            App::returnError('HTTP/1.1 409', 'Insert Error: restaurateur [username = ' . $this->username . '] already exists.');

        // check password
        if($this->password == '')
            App::returnError('HTTP/1.1 422', 'Insert Error: restaurateur password is required.');

        // proceed with insert
        $stmt = $this->conn->prepare("INSERT INTO $this->table(name, username, password, avatar, email, phone, address) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $this->name, $this->username, $this->password, $this->avatar, $this->email, $this->phone, $this->address);
        $stmt->execute();
        $this->id = $this->conn->insert_id;
    }


    /***************************************************************************
     * Update restaurateur
     *
     * @return void
     */
    public function update()
    {
        // check id
        if(!self::exists($this->id))
            App::returnError('HTTP/1.1 404', 'Update Error: restaurateur [id = ' . $this->id . '] does not exist.');

        // check username
        if(trim($this->username) == '')
            App::returnError('HTTP/1.1 422', 'Insert Error: restaurateur username is required.');
        else if(self::usernameExists($this->username, $this->id))
            App::returnError('HTTP/1.1 409', 'Insert Error: restaurateur [username = ' . $this->username . '] already exists.');

        // check password
        if($this->password == '')
            App::returnError('HTTP/1.1 422', 'Insert Error: restaurateur password is required.');

        // proceed with update
        $stmt = $this->conn->prepare("UPDATE $this->table SET name = ?, username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $this->name, $this->username, $this->password, $this->id);
        $stmt->execute();
    }


    /***************************************************************************
     * Delete restaurateur
     *
     * @return void
     */
    public function delete()
    {
        // check id
        if(!self::exists($this->id))
            App::returnError('HTTP/1.1 404', 'Delete Error: restaurateur [id = ' . $this->id . '] does not exist.');

        // proceed with delete
        $stmt = $this->conn->prepare("DELETE FROM $this->table WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
    }


    /***************************************************************************
     * Notify the customer that the booking has been confirmed
     *
     * @param Customer $recipient
     * @param string $message
     * @return void
     */
    public function notifyCustomer($recipient, $message)
    {

        // check sender id
        if(!self::exists($this->id))
            App::returnError('HTTP/1.1 404', 'Error: restaurateur [id = ' . $this->id . '] does not exist.');

        // check recipient id
        $recipient_id = $recipient->getId();
        if (!$recipient_id)
            App::returnError('HTTP/1.1 404', 'Error: recipient [id = ' . $recipient_id . '] does not exist.');

        // check message
        if (!$message)
            App::returnError('HTTP/1.1 404', 'Error: message does not exist.');

        $stmt = $this->conn->prepare("INSERT INTO $this->table_notifications(sender_id, recipient_id, message) VALUES(?, ?, ?)");
        $stmt->bind_param("iis", $this->id,  $recipient_id, $message);
        $stmt->execute();
    }


    /***************************************************************************
     * Confirm booking status
     *
     * @param $customer_id
     * @param $table_id
     * @param $code
     * @return void
     */
    public function confirmBookingStatus($customer_id, $table_id, $code)
    {
        require_once 'Booking.php';
        $bookings_table = 'bookings';

        $status = 'confirmed';
        $restaurant_id = $this->getRestaurantId();

        if (!Booking::stored($customer_id, $restaurant_id, $table_id, $code))
            App::returnError('HTTP/1.1 404', 'Update Error: Booking does not exist.');

        $stmt = $this->conn->prepare("UPDATE $bookings_table SET status = ? WHERE customer_id = ? AND restaurant_id = ? AND table_id = ? AND code = ? ");
        $stmt->bind_param("siiis", $status, $customer_id, $restaurant_id, $table_id, $code);
        $stmt->execute();
    }


    /***************************************************************************
     * Cancel booking status
     *
     * @param $customer_id
     * @param $table_id
     * @param $code
     * @return void
     */
    public function cancelBookingStatus($customer_id, $table_id, $code)
    {
        require_once 'Booking.php';
        $bookings_table = 'bookings';

        $status = 'cancelled';
        $restaurant_id = $this->getRestaurantId();

        if (!Booking::stored($customer_id, $restaurant_id, $table_id, $code))
            App::returnError('HTTP/1.1 404', 'Update Error: Booking does not exist.');

        $stmt = $this->conn->prepare("UPDATE $bookings_table SET status = ? WHERE customer_id = ? AND restaurant_id = ? AND table_id = ? AND code = ? ");
        $stmt->bind_param("siiis", $status, $customer_id, $restaurant_id, $table_id, $code);
        $stmt->execute();
    }


    /***************************************************************************
     * Mark booking status as pending
     *
     * @param $customer_id
     * @param $table_id
     * @param $code
     * @return void
     */
    public function updateBookingStatusToPending($customer_id, $table_id, $code)
    {
        require_once 'Booking.php';
        $bookings_table = 'bookings';

        $status = 'pending';
        $restaurant_id = $this->getRestaurantId();

        if (!Booking::stored($customer_id, $restaurant_id, $table_id, $code))
            App::returnError('HTTP/1.1 404', 'Update Error: Booking does not exist.');

        $stmt = $this->conn->prepare("UPDATE $bookings_table SET status = ? WHERE customer_id = ? AND restaurant_id = ? AND table_id = ? AND code = ? ");
        $stmt->bind_param("siiis", $status, $customer_id, $restaurant_id, $table_id, $code);
        $stmt->execute();
    }


    /***************************************************************************
     * Set customer visibility upon arriving restaurant
     *
     * @param $code
     * @return void
     */
    public function setCustomerVisibility($code)
    {
        require_once 'Booking.php';
        $bookings_table = 'bookings';

        $is_shown = 1;
        $restaurant_id = $this->getRestaurantId();

        if (!$code)
            App::returnError('HTTP/1.1 404', 'Update Error: Code does not exist.');

        // todo: make a condition if qr code value is not the same with booking or does not exist
        // Error: booking code with the value `$code` does not exist.

        $stmt = $this->conn->prepare("UPDATE $bookings_table SET is_shown = ? WHERE restaurant_id = ? AND code = ? ");
        $stmt->bind_param("iis", $is_shown, $restaurant_id, $code);
        $stmt->execute();
    }


    /***************************************************************************
     * Get restaurant id when available
     *
     * @return mixed|null
     */
    public function getRestaurantId()
    {
        $restaurateur = new Restaurateur($this->username, $this->password);
        $restaurateur_id = $restaurateur->getId();

        if (!$restaurateur_id)
            App::returnError('HTTP/1.1 404', 'Error: restaurateur [id = ' . $this->id . '] does not exist.');

        $stmt = $restaurateur->conn->prepare("SELECT restaurant_id FROM $restaurateur->table WHERE id = ?");
        $stmt->bind_param("i", $restaurateur_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $restaurant_id = null;
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $restaurant_id =  $row['restaurant_id'];
        }
        return $restaurant_id;
    }


    /***************************************************************************
     * Get all restaurant bookings in array
     *
     * @return array
     */
    public function getAllRestaurantBookings() {
        $bookings_table = 'bookings';

        $restaurant_id = $this->getRestaurantId();

        $stmt = $this->conn->prepare("SELECT * FROM $bookings_table WHERE restaurant_id = ?");
        $stmt->bind_param("i", $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookings = [];

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $booking = [
                    'booking_id' => $row['id'],
                    'restaurant_id' => $row['restaurant_id'],
                    'customer_id' => $row['customer_id'],
                    'table_id' => $row['table_id'],
                    'reference_number' => $row['reference_number'],
                    'code' => $row['code'],
                    'date' => $row['date'],
                    'time' => $row['time'],
                    'party_size' => $row['party_size'],
                    'status' => $row['status'],
                    'cancellation_reason' => $row['cancellation_reason'],
                    'is_shown' => $row['is_shown']
                ];
                $bookings[] = $booking;
            }
        }
        return $bookings;
    }

}
