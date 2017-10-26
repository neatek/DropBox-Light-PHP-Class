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
