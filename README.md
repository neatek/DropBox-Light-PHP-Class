# dropbox-light-php-class
# Install:

```
		require_once 'dropbox.class.php';
		$dropb = new dropbox_neatek_class('HERE_IS_YOUR_TOKEN');
```

# Example:

 ```
		$data = $dropb->request('files', 'list_folder/continue', $params);
		foreach ($dropb->get_entries() as $key => $value) {
			$dropb->entry($key);
			if($dropb->entry_is_file() && !$dropb->entry_deleted()) {
				var_dump($dropb->entry_filepath());
				var_dump($dropb->heavy_share_path($dropb->entry_filepath(),true));
			}
		}
```

# Share file ( or just use heavy_share_path($path) )
```
		Example ( or USE - heavy_share_path($path) ):
			$data = $dropb->request('files', 'list_folder/continue', $params);
			foreach ($dropb->get_entries() as $key => $value) {
				
				$dropb->entry($key);
				
				if($dropb->entry_is_file() && !$dropb->entry_deleted()) {
					
					var_dump($dropb->entry_filepath());
					
					$dropb->share_path_or_file(
						array(
							'sdk_dp_save' => true, // support next - show_last()
							'path' => $dropb->entry_filepath()
						)
					);
					
					var_dump($dropb->get_shared_link());
					$dropb->show_last(); // file can be already shared, so we can see errors here.

					$params = array(
						'sdk_dp_save' => true,
						'path' => $dropb->entry_filepath()
					);

					var_dump($params);
					
					$data = $dropb->request('sharing', 'list_shared_links', $params);
					//$dropb->show_last();
					//$dropb->show_lastest_params();
					var_dump($dropb->get_shared_links());
					var_dump($dropb->get_shared_link(true));
				}
			}
```
