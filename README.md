# IOL SSO Service

## Workflow
When a user opens a IOL project, which requires authentication against the IOL user database, the following workflow has to be followed.

1. App requests a login URL from the SSO service
2. App redirects the user to the returned URL
3. User authenticates themselves
4. SSO service redirects to the predefined return URL with a intermediate token as GET parameter
5. App exchanges the intermediate token with an access- / renew-token set at the SSO API
6. On expiration, the access-token has to be renewed using the refresh-token (via SSO API)

