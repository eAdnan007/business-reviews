(function(angular) {
	var businessReviews = angular.module('businessReviews', ['ngGPlaces']);

	businessReviews
	.config(function(ngGPlacesAPIProvider){
		ngGPlacesAPIProvider.setDefaults({
			placeDetailsKeys: ['reviews']
		})
	})
	.controller('GoogleReviewsCtrl', ['$scope', 'ngGPlacesAPI', '$http', function($scope, ngGPlacesAPI, $http) {
		$scope.row_reviews = {};
		ngGPlacesAPI
		.placeDetails({placeId: br.googleBusinessID })
		.then(function(d){
			$scope.row_reviews.google = d.reviews;
		})
		.finally(function(){
			if('undefined' != typeof $scope.row_reviews.yelp) $scope.packageReviews();
		});

		$http.get(br.yelpReviewURL).success(function(d){
			$scope.row_reviews.yelp = d.reviews;

			if('undefined' != typeof $scope.row_reviews.google) $scope.packageReviews();
		});

		$scope.packageReviews = function(){
			console.log($scope.row_reviews);
			$scope.reviews = [];
			angular.forEach($scope.row_reviews.google, function(value){
				var t                    = {};
				t.author                 = value.author_name;
				t.url                    = value.author_url;
				t.rating                 = value.rating;
				t.comment                = value.text;
				t.timestamp              = value.time;
				t.source                 = 'google';

				$scope.reviews.push(t);
			});

			angular.forEach($scope.row_reviews.yelp, function(value){
				var t                    = {};
				t.author                 = value.user.name;
				t.avatar_url             = value.user.image_url;
				t.rating                 = value.rating;
				t.comment                = value.excerpt;
				t.timestamp              = value.time_created;
				t.rating_image_large_url = value.rating_image_large_url;
				t.rating_image_small_url = value.rating_image_small_url;
				t.rating_image_url       = value.rating_image_url;
				t.source                 = 'yelp';

				$scope.reviews.push(t);
			});
		}
	}]);
})(window.angular);