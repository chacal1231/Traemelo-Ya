<?php
/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
namespace Inspius\Iscommerce\Model;

use Magento\Framework\Model\AbstractModel;

class Message extends AbstractModel
{    
    const INTEGRATION_NOT_FOUND = '';
    const INTEGRATION_TOKEN_NOT_FOUND = '';

    const REQUEST_ERROR = "Cannot get the response from the server. Please try again later.";
    
    const ERR_NO_ROUTE = "Please pass an action to the request.";

    const USER_NO_ROUTE = "Please pass an action to user api request.";

    // login
    const USER_LOGIN_FAILED = "The email or password is invalid. Please try again.";
    const USER_LOGIN_INVALID_DATA = "Please check the username and password again.";
    const USER_LOGIN_EMPTY_PASSWORD = 'Please enter your password.';
    const USER_LOGIN_EMPTY_EMAIL = 'Please enter your email';
    const USER_LOGIN_INVALID_EMAIL = 'The entered email format is incorrect. Please enter your correct email.';
    const USER_LOGIN_INVALIDCOMBO = 'Please check the username and password again.';
    const USER_LOGIN_EMPTY_USERNAME = 'Please enter your username.';
    const USER_LOGIN_INVALID_USERNAME = 'Cannot find this username. Please enter your username again.';
    const USER_LOGIN_INCORRECT_PASSWORD = 'Incorrect password. Please enter your password again.';

    // register
    const USER_REGISTER_INVALID_DATA = 'Register failed. Please enter the data again.';
    const USER_REGISTER_EMPTY_USER_LOGIN = 'Username cannot be empty.';
    const USER_REGISTER_USER_LOGIN_TOO_LONG = 'The username is too long. Plase try again.';
    const USER_REGISTER_EXISTING_USER_LOGIN = 'The username has been used. Please try another username.';
    const USER_REGISTER_INVALID_USERNAME = 'The username contain invalid word or character. Please try again.';
    const USER_REGISTER_USER_NICENAME_TOO_LONG = 'The user nicename is too long.';
    const USER_REGISTER_EXISTING_USER_EMAIL = 'The email has been used. Please try again.';

    // forgot password
    const USER_FORGOT_INVALID_DATA = 'Please enter your email address or username.';
    const USER_FORGOT_USER_NOT_EXIST = 'Cannot find user with the provided data. Please try again.';
    const USER_FORGOT_CANNOT_RESET = 'Cannot reset the password for this user at the moment. Please try again later.';

    // update user
    const USER_UPDATE_FAILED = 'Cannot update the user data at the moment. Please try again later.';
    const USER_UPDATE_CUSTOMER_NOT_FOUND = 'Cannot find the customer to update.';
    const USER_UPDATE_MISMATCH_CONFIRMATION = 'Please make sure your new password and the password confirmation is matched.';
    const USER_UPDATE_WRONG_PASSWORD = 'Please enter your current password to update new password.';

    // review
    const REVIEW_ID_NOT_FOUND = 'Please enter product id to get review.';
    const REVIEW_ADD_NEW_FAILED = 'Cannot add new review. Please try again.';
    const REVIEW_INVALID_DATA = 'Please check the input information again.';
    const REVIEW_ADD_NEW_ID_NOT_FOUND = 'Cannot add new review. Please add product id.';
    const REVIEW_ADD_NEW_DUPLICATED = 'Duplicate comment detected; it looks as though you’ve already said that!';

    // order
    const ORDER_INVALID_DATA = 'Please enter all the required attribute';
    const ORDER_NOT_FOUND = 'Cannot find the order with the given id';
    const ORDER_CANNOT_CREATE = 'Cannot create new order';
    const ORDER_CANNOT_FIND_CUSTOMER = 'Cannot create order for this customer.';
    const ORDER_CANNOT_CAPTURE = 'Cannot capture this order. This order will be cancelled';

    // product
    const PRODUCT_NOT_FOUND = 'Cannot find the product with the given id';
    const CATEGORY_NOT_FOUND = 'Cannot find the category with the given id';

    // cart
    const CART_CANNOT_CREATE = 'Cannot create cart. Please try again';
    const CART_NOT_FOUND = 'Cannot find this cart. Please try again';
    const CART_NO_ITEM_FOUND = 'Please add a product to continue';
    const CART_CANNOT_GET_PRICE = 'Cannot get price of this cart.';
    const CART_CANNOT_GET_PAYMENT_METHOD = 'Cannot get payment method for this order.';
    const CART_CANNOT_GET_SHIPPING_METHOD = 'Cannot get shipping method for this order';
    const CART_CANNOT_SET_SHIPPING_METHOD = 'Cannot set shipping method for this order';
}