
<script type="text/javascript">
const AUTH0_ID = '<?= $app['config/auth0/secret'] ?>';
const AUTH0_DOMAIN = '<?= $app['config/auth0/domain'] ?>';

const isLogoutAction = location.search.indexOf('logout=1') > -1;
const logoutButton = document.getElementById('logout');

const lock = new Auth0Lock('<?= $app['config/auth0/secret'] ?>', '<?= $app['config/auth0/domain'] ?>', {
  container: 'login-container',
  allowSignUp: false,
  theme: {
    logo: 'https://rootz.com/wp-content/uploads/2018/12/rootz_logo-01.png',
    primaryColor: '#2a2c74',
    labeledSubmitButton: false
  },
  languageDictionary: {
    //title: '<?= $app['app.name'] ?>'
    title: ''
  },
  auth: {
    sso: false,
    params: {
      scopes: '<?= $app['config/auth0/scope'] ?>'
    }
  }
});

function logout(isAction) {
  logoutButton.classList.add('uk-hidden');
  localStorage.removeItem('cockpit.auth0.accessToken');

  if(isAction) {
    lock.logout({
      returnTo: '<?= $app->getSiteUrl(true) ?>/auth/login'
    });
  }
}

function loggedIn(user, isFresh) {
  logoutButton.classList.remove('uk-hidden');
  console.log('user authorized, redirecting...');

  setTimeout(() => {
    App.reroute('/');
  }, 500);
}

function authorize(token, isFresh) {
  if(!token) return;

  const data = { token: token, auth0: true };

  fetch('/api/auth0/authorize', 
    {
      method: 'POST',
      mode: 'cors',
      cache: 'no-cache',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(response => {
      if(!response) {
        alert('invalid response from server');
        return;
      }

      if(!response.authorized) {
        console.warn('authorization failed:', response);
        logout();
      } else if(response.authorized == true) {
        console.log('user authorized:', response);
        loggedIn(response, isFresh);
      }
    })
    .catch(err => {
      console.error('Authorization failure:', err.message);
      console.error(err.stack);
      logout();
    });
}

function reviveSession() {
  const maybeToken = window.localStorage.getItem('cockpit.auth0.accessToken');

  if(maybeToken) {
    if(isLogoutAction) {
      logout();
      return;
    }

    authorize(maybeToken);
  }
}

//reviveSession();
//lock.show();

lock.on('authenticated', auth => {
  // validate the token first
  lock.getUserInfo(auth.accessToken, (err, profile) => {
    if(err) {
      console.error('Unexpected error while authenticating:', err);
      console.error(err.stack);
      alert(`Unexpected error: ${err.message}`);
      return;
    }

    window.localStorage.setItem('cockpit.auth0.accessToken', auth.accessToken);
    authorize(auth.accessToken, true);
  });
});
</style>
