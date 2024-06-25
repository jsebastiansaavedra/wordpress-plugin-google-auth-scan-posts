import { createRoot, render, StrictMode, createInterpolateElement, useState, useEffect } from '@wordpress/element';
import { Button, TextControl, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import "./scss/style.scss"

const domElement = document.getElementById( window.wpmudevPluginTest.dom_element_id );

const WPMUDEV_PluginTest = () => {
    const [clientId, setClientId] = useState('');
    const [clientSecret, setClientSecret] = useState('');
    const [notice, setNotice] = useState('');

    const handleClick = () => {
        // Check if the user is logged in and has permissions
        if ( isLoggedIn && hasPermissions ){
            apiFetch({ 
                path: '/wpmudev/v1/auth/auth-url', 
                method: 'POST', 
                data: { 
                    client_id: clientId, 
                    client_secret: clientSecret 
                }
            }).then(response => {
                setNotice('Credentials saved successfully.');
            }).catch(error => {
                setNotice('An error occurred. Please try again.', error);
            });
        }
    }

    function isLoggedIn(){
        wp.apiFetch({ path: '/wp/v2/users/me' })
            .then(response => {
                if ( response.id ) {
                    return true; // User is logged in
                }else {
                    return false; // User is not logged in
                }
            })
            .catch(error => {
                // Error fetching user data
                setNotice('An error occurred with login verification. Please try again.', error);
                return false;
            });
    }

    function hasPermissions(){
        wp.apiFetch({ path: `/wp/v2/users/me/capabilities` })
            .then(response => {
                // Check if the capability is present in the user's capabilities
                if (response && response.capabilities && response.capabilities[capability]) {
                    return true; // User has the required capability
                } else {
                    return false; // User does not have the required capability
                }
            })
            .catch(error => {
                setNotice('Error checking user permissions:', error);
                return false;
        });
    }


    return (
        <>
            <div class="sui-header">
                <h1 class="sui-header-title">
                    Settings
                </h1>
            </div>
    
            <div className="sui-box">
    
                <div className="sui-box-header">
                    <h2 className="sui-box-title">Set Google credentials</h2>
                </div>
    
                <div className="sui-box-body">
                    <div className="sui-box-settings-row">
                        <TextControl
                            help={createInterpolateElement(
                                'You can get Client ID from <a>here</a>.',
                                {
                                    a: <a href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
                                }
                            )}
                            label="Client ID"
                            value={clientId}
                            onChange={setClientId}
                        />
                    </div>
    
                    <div className="sui-box-settings-row">
                        <TextControl
                            help={createInterpolateElement(
                                'You can get Client Secret from <a>here</a>.',
                                {
                                    a: <a href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
                                }
                            )}
                            label="Client Secret"
                            type="password"
                            value={clientSecret}
                            onChange={setClientSecret}
                        />
                    </div>
    
                    <div className="sui-box-settings-row">
                        <span>Please use this url <em>{window.wpmudevPluginTest.returnUrl}</em> in your Google API's <strong>Authorized redirect URIs</strong> field</span>
                    </div>
                </div>
    
                <div className="sui-box-footer">
                    <div className="sui-actions-right">
                        <Button
                            variant="primary"
                            onClick={ handleClick }
                        >
                            Save
                        </Button>
    
                    </div>
                </div>
    
            </div>

            {notice && (
                <Notice status="info" onRemove={() => setNotice('')}>
                    {notice}
                </Notice>
            )}
    
        </>
    );
}

// Ensure only one rendering approach is used
if ( createRoot ) {
    createRoot( domElement ).render(<StrictMode><WPMUDEV_PluginTest/></StrictMode>);
} else {
    render( <StrictMode><WPMUDEV_PluginTest/></StrictMode>, domElement );
}
