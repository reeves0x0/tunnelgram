import Container from './Container.html';
import UserStore from './UserStore';
import {Nymph} from 'nymph-client';
import {User} from 'tilmeld-client';

import userKeyPairWatch from './App/userKeyPairWatch.js';

userKeyPairWatch(User);

const store = new UserStore({
  todos: [],
  sort: 'name',
  archived: false
});

const app = new Container({
  target: document.querySelector('main'),
  data: {
    brand: 'ESText'
  },
  store
});

// useful for debugging!
window.store = store;
window.Nymph = Nymph;
