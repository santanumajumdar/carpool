<?php
Class payment_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	function get_gross_monthly_sales($year)
	{
		$this->db->select('SUM(coupon_discount) as coupon_discounts');
		$this->db->select('SUM(gift_card_discount) as gift_card_discounts');
		$this->db->select('SUM(subtotal) as product_totals');
		$this->db->select('SUM(shipping) as shipping');
		$this->db->select('SUM(tax) as tax');
		$this->db->select('SUM(total) as total');
		$this->db->select('YEAR(ordered_on) as year');
		$this->db->select('MONTH(ordered_on) as month');
		$this->db->group_by(array('MONTH(ordered_on)'));
		$this->db->order_by("ordered_on", "desc");
		$this->db->where('YEAR(dtcreateddate)', $year);
		
		return $this->db->get('tbl_orders')->result();
	}
	
	function get_sales_years()
	{
		$this->db->order_by("ordered_on", "desc");
		$this->db->select('YEAR(ordered_on) as year');
		$this->db->group_by('YEAR(ordered_on)');
		$records	= $this->db->get('orders')->result();
		$years		= array();
		foreach($records as $r)
		{
			$years[]	= $r->year;
		}
		return $years;
	}
	
	function get_orders($search=false, $sort_by='', $sort_order='DESC', $limit=0, $offset=0)
	{			
		if ($search)
		{
			if(!empty($search->term))
			{
				//support multiple words
				$term = explode(' ', $search->term);

				foreach($term as $t)
				{
					$not		= '';
					$operator	= 'OR';
					if(substr($t,0,1) == '-')
					{
						$not		= 'NOT ';
						$operator	= 'AND';
						//trim the - sign off
						$t		= substr($t,1,strlen($t));
					}

					$like	= '';
					$like	.= "( `order_number` ".$not."LIKE '%".$t."%' " ;
					$like	.= $operator." `bill_firstname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `bill_lastname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `ship_firstname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `ship_lastname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `status` ".$not."LIKE '%".$t."%' ";
					$like	.= $operator." `notes` ".$not."LIKE '%".$t."%' )";

					$this->db->where($like);
				}	
			}
			if(!empty($search->start_date))
			{
				$this->db->where('dtcreateddate >=',$search->start_date);
			}
			if(!empty($search->end_date))
			{
				//increase by 1 day to make this include the final day
				//I tried <= but it did not function. Any ideas why?
				$search->end_date = date('Y-m-d', strtotime($search->end_date)+86400);
				$this->db->where('dtcreateddate <',$search->end_date);
			}
			
		}
		
		if($limit>0)
		{
			$this->db->limit($limit, $offset);
		}
		if(!empty($sort_by))
		{
			$this->db->order_by($sort_by, $sort_order);
		}
		
		$this->db->join('tbl_provider', 'tbl_provider.prodid = tbl_orders.provider_id');
		$this->db->join('tbl_plantype', 'tbl_plantype.planid = tbl_orders.plan_id');
		$this->db->where('orderstatus',1);		
		$this->db->where('paymentstatus',0);
		return $this->db->get('tbl_orders')->result();
	}
	
	function get_orders_count($search=false)
	{			
		if ($search)
		{
			if(!empty($search->term))
			{
				//support multiple words
				$term = explode(' ', $search->term);

				foreach($term as $t)
				{
					$not		= '';
					$operator	= 'OR';
					if(substr($t,0,1) == '-')
					{
						$not		= 'NOT ';
						$operator	= 'AND';
						//trim the - sign off
						$t		= substr($t,1,strlen($t));
					}

					$like	= '';
					$like	.= "( `order_number` ".$not."LIKE '%".$t."%' " ;
					$like	.= $operator." `bill_firstname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `bill_lastname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `ship_firstname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `ship_lastname` ".$not."LIKE '%".$t."%'  ";
					$like	.= $operator." `status` ".$not."LIKE '%".$t."%' ";
					$like	.= $operator." `notes` ".$not."LIKE '%".$t."%' )";

					$this->db->where($like);
				}	
			}
			if(!empty($search->start_date))
			{
				$this->db->where('dtcreateddate >=',$search->start_date);
			}
			if(!empty($search->end_date))
			{
				$this->db->where('dtcreateddate <',$search->end_date);
			}
			
		}
		
		return $this->db->count_all_results('tbl_orders');
	}

	
	
	//get an individual customers orders
	function get_customer_orders($id, $offset=0)
	{
		$this->db->join('order_items', 'orders.id = order_items.order_id');
		$this->db->order_by('ordered_on', 'DESC');
		return $this->db->get_where('tbl_orders', array('customer_id'=>$id), 15, $offset)->result();
	}
	
	function count_customer_orders($id)
	{
		$this->db->where(array('customer_id'=>$id));
		return $this->db->count_all_results('tbl_orders');
	}
	
	function get_order($id)
	{
		$this->db->where('order_id', $id);
		$result 			= $this->db->get('tbl_orders');
		
		$order				= $result->row();
	//	$order->contents	= $this->get_items($order->order_id);
		
		return $order;
	}
	
	function get_credits($id)
	{
		$this->db->where('provider_id', $id);
		$result 			= $this->db->get('tbl_provider_credits');
		
		$credits				= $result->row();
		
		return $credits;
	}
	
	function get_items($id)
	{
		$this->db->select('order_id, contents');
		$this->db->where('order_id', $id);
		$result	= $this->db->get('order_items');
		
		$items	= $result->result_array();
		
		$return	= array();
		$count	= 0;
		foreach($items as $item)
		{

			$item_content	= unserialize($item['contents']);
			
			//remove contents from the item array
			unset($item['contents']);
			$return[$count]	= $item;
			
			//merge the unserialized contents with the item array
			$return[$count]	= array_merge($return[$count], $item_content);
			
			$count++;
		}
		return $return;
	}
	
	function delete($id)
	{
	
		$this->db->where('order_id', $id);
		$this->db->delete('tbl_orders');
		
		
	}
	
	function save_credit($data)
	{
	
	
	if (isset($data['credit_id']))
		{
			$this->db->where('provider_id', $data['provider_id']);
			$this->db->update('tbl_provider_credits', $data);
			//echo $this->db->last_query();
			$id = $data['credit_id'];
			
			// we don't need the actual order number for an update
			$credit_number = $id;
		}
		else
		{
			
			$this->db->insert('tbl_provider_credits', $data);
			$id = $this->db->insert_id();
			
			
			$credit_number = $id;
	
		
		}
		
		return $credit_number;

	}
	
	
		function save_order($data)
	{
	
	
	if (isset($data['order_id']))
		{
			$this->db->where('order_id', $data['order_id']);
			$this->db->update('tbl_orders', $data);
			//echo $this->db->last_query();
			$id = $data['order_id'];
			
			// we don't need the actual order number for an update
			$order_number = $id;
		}
		else
		{
			
			$this->db->insert('tbl_orders', $data);
			$id = $this->db->insert_id();
			
			//create a unique order number
			//unix time stamp + unique id of the order just submitted.
			$order	= array('order_number'=> date('U').$id);
			
			//update the order with this order id
			$this->db->where('order_id', $id);
			$this->db->update('tbl_orders', $order);
						
			//return the order id we generated
			$order_number = $order['order_number'];
	
		
		}
		
		return $order_number;

	}
	
	function get_best_sellers($start, $end)
	{
		if(!empty($start))
		{
			$this->db->where('ordered_on >=', $start);
		}
		if(!empty($end))
		{
			$this->db->where('ordered_on <',  $end);
		}
		
		// just fetch a list of order id's
		$orders	= $this->db->select('id')->get('orders')->result();
		
		$items = array();
		foreach($orders as $order)
		{
			// get a list of product id's and quantities for each
			$order_items	= $this->db->select('product_id, quantity')->where('order_id', $order->id)->get('order_items')->result_array();
			
			foreach($order_items as $i)
			{
				
				if(isset($items[$i['product_id']]))
				{
					$items[$i['product_id']]	+= $i['quantity'];
				}
				else
				{
					$items[$i['product_id']]	= $i['quantity'];
				}
				
			}
		}
		arsort($items);
		
		// don't need this anymore
		unset($orders);
		
		$return	= array();
		foreach($items as $key=>$quantity)
		{
			$product				= $this->db->where('id', $key)->get('products')->row();
			if($product)
			{
				$product->quantity_sold	= $quantity;
			}
			else
			{
				$product = (object) array('sku'=>'Deleted', 'name'=>'Deleted', 'quantity_sold'=>$quantity);
			}
			
			$return[] = $product;
		}
		
		return $return;
	}
	
}