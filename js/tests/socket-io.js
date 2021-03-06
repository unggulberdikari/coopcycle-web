var assert = require('assert');
var fs = require('fs');
var io = require('socket.io-client');

var TestUtils = require('./utils');

var utils = new TestUtils();

var pub = require('../api/RedisClient')({
  prefix: process.env.COOPCYCLE_DB_NAME + '_test:',
  url: process.env.COOPCYCLE_REDIS_DSN
});

var initUsers = function() {
  return new Promise(function(resolve, reject) {
    Promise.all([
      utils.createUser('bill',  ['ROLE_USER']),
      utils.createUser('sarah', ['ROLE_USER', 'ROLE_ADMIN']),
      utils.createUser('bob',   ['ROLE_USER', 'ROLE_RESTAURANT']),
      utils.createUser('wendy', ['ROLE_USER']),
    ])
    .then(function(users) {
      const [ bill, sarah, bob ] = users
      utils
        .createRestaurant('foo', { latitude: 48.856613, longitude: 2.352222 })
        .then(restaurant => {
          bob.addRestaurant(restaurant).then(() => {
            resolve()
          })
        })
    })
    .catch(function(e) {
      reject(e)
    })
  })
}

function createSocket(username) {
  return io.connect('http://127.0.0.1:8001', {
    path: '/tracking/socket.io',
    forceNew: true,
    transports: ['websocket'],
    query: {
      token: utils.createJWT(username),
    }
  })
}

describe('Connect to Socket.IO', function() {

  before('Waiting for server', function() {
    this.timeout(30000)
    return new Promise(function (resolve, reject) {
      utils.waitServerUp('127.0.0.1', 8001).then(function() {
        resolve()
      })
    })
  });

  beforeEach('Cleaning db & initializing users', function() {
    this.timeout(30000)
    return new Promise(function (resolve, reject) {
      utils.cleanDb()
        .then(function() {
          initUsers().then(function() {
            resolve()
          })
        })
    })
  });

  it('should return authentication error without JWT', function() {
    return new Promise((resolve, reject) => {

      var socket = io.connect('http://127.0.0.1:8001', {
        path: '/tracking/socket.io',
        forceNew: true,
        transports: ['websocket'],
      });

      socket.on('error', (error) => {
        assert.equal('Authentication error', error);
        resolve();
      });

    })
  });

  it('should connect successfully with valid JWT', function() {
    return new Promise((resolve, reject) => {
      var socket = createSocket('bill');
      socket.on('connect', function() {
        resolve();
      })
    })
  });

  [
    'order:accepted',
    'order:picked',
    'order:dropped',
    'order:fulfilled'
  ].forEach((eventName) => {
    it(`should emit "${eventName}" message to expected users`, function() {
      this.timeout(3000)
      return new Promise((resolve, reject) => {

        const socketForBill = createSocket('bill')
        const socketForSarah = createSocket('sarah')

        const data = {
          restaurant: {
            id: 1
          },
        }

        const message = {
          name: eventName,
          data
        }

        // Wait for all sockets to connect, and send message
        Promise.all([
          new Promise((resolve, reject) => socketForBill.on('connect', () => resolve())),
          new Promise((resolve, reject) => socketForSarah.on('connect', () => resolve())),
        ]).then(() => {
          pub.prefixedPublish('users:bill', JSON.stringify(message))
        })

        socketForBill.on(eventName, function(message) {
          assert.deepEqual(data, message);
          resolve();
        })

        socketForSarah.on(eventName, function(message) {
          reject(new Error(`Message "${eventName}" should not have been emitted`));
        })

      })
    });
  });

  it(`should emit "tracking" message to admins`, function() {
      this.timeout(3000)
      return new Promise((resolve, reject) => {

        // Use "sarah" has role ROLE_ADMIN
        const socketForSarah = createSocket('sarah')
        const socketForBill = createSocket('bill')

        // Wait for all sockets to connect, and send message
        Promise.all([
          new Promise((resolve, reject) => socketForBill.on('connect', () => resolve())),
          new Promise((resolve, reject) => socketForSarah.on('connect', () => resolve())),
        ]).then(() => {
          setTimeout(() => utils.updateLocation('bob', 48.856613, 2.352222), 500)
        })

        socketForSarah.on('tracking', function(message) {
          assert.deepEqual({ user: 'bob', coords: { lat: 48.856613, lng: 2.352222 } }, message);
          resolve();
        })

        socketForBill.on('tracking', function(message) {
          reject(new Error(`Message "tracking" should not have been emitted`));
        })

      })
    });

});
