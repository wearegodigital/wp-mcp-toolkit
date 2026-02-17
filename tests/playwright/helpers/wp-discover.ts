import { APIRequestContext } from '@playwright/test';

export interface PostTypeInfo {
	slug: string;
	label: string;
	restBase: string;
	samplePost?: {
		id: number;
		title: string;
		url: string;
	};
}

export interface TemplateInfo {
	post_type: string;
	reference_post_id: number;
	section_count: number;
	placeholder_count: number;
	has_acf_fields: boolean;
}

/**
 * Discover all public post types from the WP REST API.
 */
export async function discoverPostTypes(
	request: APIRequestContext,
	baseURL: string
): Promise<PostTypeInfo[]> {
	const response = await request.get(`${baseURL}/wp-json/wp/v2/types`);
	const types = await response.json();

	const postTypes: PostTypeInfo[] = [];

	for (const [slug, typeData] of Object.entries(types) as [string, any][]) {
		if (!typeData.rest_base || slug === 'attachment') continue;

		const info: PostTypeInfo = {
			slug,
			label: typeData.name,
			restBase: typeData.rest_base,
		};

		// Try to get a sample post.
		try {
			const postsResponse = await request.get(
				`${baseURL}/wp-json/wp/v2/${typeData.rest_base}?per_page=1&status=publish`
			);
			if (postsResponse.ok()) {
				const posts = await postsResponse.json();
				if (posts.length > 0) {
					info.samplePost = {
						id: posts[0].id,
						title: posts[0].title?.rendered || posts[0].title,
						url: posts[0].link,
					};
				}
			}
		} catch {
			// Skip types that don't have accessible REST endpoints.
		}

		postTypes.push(info);
	}

	return postTypes;
}

/**
 * Discover saved MCP templates via the plugin REST endpoint.
 */
export async function discoverTemplates(
	request: APIRequestContext,
	baseURL: string
): Promise<TemplateInfo[]> {
	try {
		const response = await request.get(`${baseURL}/wp-json/wpmcp/v1/templates`);
		if (response.ok()) {
			return await response.json();
		}
	} catch {
		// Endpoint may not exist if REST route not registered.
	}
	return [];
}
